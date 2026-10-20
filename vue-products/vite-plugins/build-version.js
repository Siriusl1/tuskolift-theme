/**
 * Vite 插件：构建结束时生成 dist/assets/version.json
 *
 * 解决的问题
 * ---------
 * vite.config.js 把产物文件名固定成 assets/index.js 和 assets/index.css
 * （没有内容哈希），所以每次重新构建后浏览器都可能继续用缓存里的旧文件。
 * 原来的做法是「手工把 functions.php 里的 '1.5.0' +1」——很容易忘记，
 * 而忘记的后果是运营在后台改了东西、前台却看不到变化，很难排查。
 *
 * 这里的做法
 * ---------
 * 构建完成时，对所有产物内容算一个哈希，写进 version.json：
 *
 *     { "version": "a1b2c3d4e5f6", "built_at": "2026-10-15T04:00:00.000Z", "files": {...} }
 *
 * functions.php 的 my_enqueue_vue_products() 会优先读这个文件，
 * 拿它的 version 当 enqueue 的版本参数（?ver=a1b2c3d4）。
 * 内容没变 → 哈希不变 → 缓存继续命中；内容变了 → 哈希自动变 → 浏览器强制重取。
 * 于是「版本号」不再需要人工维护。
 *
 * 兼容性
 * -----
 * functions.php 读取失败（例如忘了 npm run build、或手工删了 version.json）时，
 * 会回退到主题里手写的常量 TK_VUE_VERSION，保证站点不会因此白屏。
 */

import { createHash } from 'node:crypto';

/**
 * 需要参与哈希计算的文件。
 *
 * 直接把 entryFileNames / assetFileNames 固定出来的名字写进来即可；
 * 用固定列表而不是「遍历 dist」是为了让哈希只随真正的产物变化，
 * 不会被 version.json 自己、或 .gz/.br 之类派生物干扰。
 */
const TRACKED_FILES = ['assets/index.js', 'assets/index.css'];

/**
 * 生成短哈希：取前 12 位十六进制，足够区分内容，也不会让 URL 太长。
 *
 * @param {Buffer} buf 内容。
 * @returns {string}
 */
function shortHash(buf) {
  return createHash('sha256').update(buf).digest('hex').slice(0, 12);
}

/**
 * 自定义 Vite 插件。
 *
 * 用 generateBundle 拿到内存产物：此时文件内容已确定，
 * 而且可以像 precompress 插件一样直接 emitFile 写进 dist，
 * 不需要构建后再去磁盘上读一遍。
 *
 * @returns {import('vite').Plugin}
 */
function buildVersionPlugin() {
  return {
    name: 'tuskolift:build-version',
    apply: 'build',
    // 参与哈希必须看到最终内容，放在 precompress 之前/之后都不影响结果，
    // 但保持 post 以确保排在 Vite 内置处理之后。
    enforce: 'post',

    /**
     * @param {object} _options 构建选项。
     * @param {Record<string, any>} bundle 产物集合。
     */
    generateBundle(_options, bundle) {
      /** @type {Record<string, string>} */
      const fileHashes = {};

      // 累积哈希：把所有被跟踪文件的内容依次喂进同一个 hash 实例，
      // 这样任一文件变化都会改变最终版本号。
      const accumulator = createHash('sha256');

      for (const name of TRACKED_FILES) {
        const output = bundle[name];

        if (!output) {
          // 某个文件不存在（例如只有 JS 没有 CSS）时跳过，不算错误。
          continue;
        }

        const buf = output.type === 'asset'
          ? Buffer.from(output.source)
          : Buffer.from(output.code, 'utf8');

        const h = shortHash(buf);
        fileHashes[name] = h;
        accumulator.update(name).update(buf);
      }

      const version = accumulator.digest('hex').slice(0, 12);

      const payload = {
        version,
        built_at: new Date().toISOString(),
        files: fileHashes,
      };

      // 写成产物，由 Vite 统一落盘到 dist/assets/version.json
      this.emitFile({
        type: 'asset',
        fileName: 'assets/version.json',
        source: JSON.stringify(payload, null, 2) + '\n',
      });

      console.log(`\n  Vue 产物版本号（已写入 dist/assets/version.json）：${version}\n`);
    },
  };
}

export default buildVersionPlugin;
