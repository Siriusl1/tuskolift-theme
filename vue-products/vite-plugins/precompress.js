/**
 * Vite 插件：构建时额外生成 .gz 和 .br 预压缩文件
 *
 * 为什么需要
 * ---------
 * 服务器端 Gzip/Brotli 是「每个请求现场压缩」，CPU 开销随访问量线性增长。
 * 构建时预压缩只在打包时压一次，服务器只需要按 Accept-Encoding 直接发文件，
 * 通常配一条 nginx 规则即可：
 *
 *     gzip_static on;
 *     brotli_static on;
 *
 * 本插件只负责「生成文件」，不碰任何服务器配置 —— 按需求，
 * 服务器 Gzip/Brotli 留到 staging / 生产再开。
 *
 * 只压文本类资源
 * -------------
 * PNG/JPG/WOFF2/MP4 本身已是压缩格式，再压几乎没有收益（有时还更大），
 * 所以按扩展名白名单处理，避免 dist 里堆一堆没用的 .gz 垃圾。
 *
 * 压缩级别取舍
 * -----------
 * gzip 用 9（构建时多花几百毫秒，换来最小体积，运行时零成本）。
 * brotli 用 11 是最高档，对小文件也很值得；质量 11 时压缩较慢，
 * 但本站产物只有几十 KB，实测在 1 秒以内。
 *
 * node:zlib 在所有 Node 版本都自带，不需要额外依赖。
 */

import { gzipSync, brotliCompressSync, constants as zlibConstants } from 'node:zlib';
import { extname } from 'node:path';

/**
 * 需要预压缩的文件类型。
 * 只列文本/可压缩格式；图片、字体、视频不在其中。
 */
const COMPRESSIBLE_EXTENSIONS = new Set([
  '.js',
  '.mjs',
  '.cjs',
  '.css',
  '.html',
  '.svg',
  '.json',
  '.map',
  '.txt',
  '.xml',
]);

/**
 * 小于这个字节数就不生成压缩文件。
 *
 * 依据：HTTP 响应头本身就有几百字节（Content-Encoding、Vary、ETag 等），
 * 一个 200 字节的 JS 压完可能只有 150 字节，收益抵不过多一个文件的复杂度。
 * WordPress 那边是靠「文件不存在就回退原文件」来兜底的，所以跳过是安全的。
 */
const MIN_SIZE_BYTES = 1024;

/**
 * 自定义 Vite 插件。
 *
 * 在 generateBundle 里拿到内存产物并顺手 emit 压缩版本，
 * 比在磁盘上重新找文件更可靠（不必猜 outDir 与文件名的关系）。
 *
 * @returns {import('vite').Plugin}
 */
function precompressPlugin() {
  /** @type {{ file: string, raw: number, gzip: number, brotli: number }[]} */
  const report = [];

  return {
    name: 'tuskolift:precompress',
    apply: 'build',
    // 放在最后，确保其他插件已经决定了最终的产物内容。
    enforce: 'post',

    /**
     * Vite 的 generateBundle 拿到的是内存中的产物，直接遍历即可，
     * 不需要再去磁盘上猜路径。
     *
     * @param {object} _options 构建选项。
     * @param {Record<string, any>} bundle 产物集合。
     */
    generateBundle(_options, bundle) {
      for (const [fileName, output] of Object.entries(bundle)) {
        const ext = extname(fileName).toLowerCase();

        if (!COMPRESSIBLE_EXTENSIONS.has(ext)) {
          continue;
        }

        // 只处理有实际内容的产物（asset 用 source，chunk 用 code）。
        let source;
        if (output.type === 'asset') {
          source = typeof output.source === 'string'
            ? Buffer.from(output.source, 'utf8')
            : Buffer.from(output.source);
        } else {
          source = Buffer.from(output.code, 'utf8');
        }

        if (source.length < MIN_SIZE_BYTES) {
          continue;
        }

        const gz = gzipSync(source, { level: 9 });
        const br = brotliCompressSync(source, {
          params: {
            // BROTLI_PARAM_QUALITY 11 = 最高压缩率（构建时一次性成本）。
            [zlibConstants.BROTLI_PARAM_QUALITY]: 11,
            // 让 brotli 按「网页资源」而不是通用数据来选窗口，通常更小。
            [zlibConstants.BROTLI_PARAM_MODE]: zlibConstants.BROTLI_MODE_TEXT,
          },
        });

        // 作为额外产物写进 dist，交给 Vite 统一落盘。
        this.emitFile({
          type: 'asset',
          fileName: `${fileName}.gz`,
          source: gz,
        });

        this.emitFile({
          type: 'asset',
          fileName: `${fileName}.br`,
          source: br,
        });

        report.push({
          file: fileName,
          raw: source.length,
          gzip: gz.length,
          brotli: br.length,
        });
      }
    },

    /**
     * 构建结束后打印一张体积对照表，方便一眼看出压缩收益。
     */
    closeBundle() {
      if (!report.length) {
        return;
      }

      const kb = (n) => `${(n / 1024).toFixed(1)} KB`;

      const lines = [
        '',
        '  预压缩结果（.gz / .br 已写入 dist）：',
        '  ┌────────────────────────────┬──────────┬──────────┬──────────┐',
        '  │ 文件                       │ 原始     │ gzip     │ brotli   │',
        '  ├────────────────────────────┼──────────┼──────────┼──────────┤',
      ];

      for (const row of report) {
        lines.push(
          `  │ ${row.file.padEnd(26)} │ ${kb(row.raw).padStart(8)} │ ${kb(row.gzip).padStart(8)} │ ${kb(row.brotli).padStart(8)} │`
        );
      }

      lines.push('  └────────────────────────────┴──────────┴──────────┴──────────┘');
      lines.push('');
      lines.push('  提示：服务器需要在 staging / 生产开启 gzip_static / brotli_static 才会用到这些文件。');
      lines.push('');

      console.log(lines.join('\n'));
    },
  };
}

export default precompressPlugin;
