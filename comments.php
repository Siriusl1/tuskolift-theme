<?php
/**
 * The template for displaying comments
 *
 * This is the template that displays the area of the page that contains both the current comments
 * and the comment form.
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package TUSKOLIFT
 */

/*
 * If the current post is protected by a password and
 * the visitor has not yet entered the password we will
 * return early without loading the comments.
 */
if ( post_password_required() ) {
	return;
}
?>

<div id="comments" class="comments-area">

	<?php
	// You can start editing here -- including this comment!
	if ( have_comments() ) :
		?>
		<h2 class="comments-title">
			<?php
			$tuskolift_comment_count = get_comments_number();
			if ( '1' === $tuskolift_comment_count ) {
				printf(
					/* translators: 1: title. */
					esc_html__( 'One thought on &ldquo;%1$s&rdquo;', 'tuskolift' ),
					'<span>' . wp_kses_post( get_the_title() ) . '</span>'
				);
			} else {
				printf( 
					/* translators: 1: comment count number, 2: title. */
					esc_html( _nx( '%1$s thought on &ldquo;%2$s&rdquo;', '%1$s thoughts on &ldquo;%2$s&rdquo;', $tuskolift_comment_count, 'comments title', 'tuskolift' ) ),
					number_format_i18n( $tuskolift_comment_count ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					'<span>' . wp_kses_post( get_the_title() ) . '</span>'
				);
			}
			?>
		</h2><!-- .comments-title -->

		<?php the_comments_navigation(); ?>

		<ol class="comment-list">
			<?php
			wp_list_comments(
				array(
					'style'      => 'ol',
					'short_ping' => true,
				)
			);
			?>
		</ol><!-- .comment-list -->

		<?php
		the_comments_navigation();

		// If comments are closed and there are comments, let's leave a little note, shall we?
		if ( ! comments_open() ) :
			?>
			<p class="no-comments"><?php esc_html_e( 'Comments are closed.', 'tuskolift' ); ?></p>
			<?php
		endif;

	endif; // Check for have_comments().

	/*
	 * 评论表单。
	 * 显式传入英文文案：不依赖站点语言（本站后台是 zh_CN），
	 * 站点面向海外客户，评论表单必须是英文。
	 */
	comment_form(
		array(
			'title_reply'          => 'Leave a Comment',
			'title_reply_to'       => 'Leave a Reply to %s',
			'cancel_reply_link'    => 'Cancel reply',
			'label_submit'         => 'POST COMMENT',
			'comment_notes_before' => '<p class="comment-notes">Your email address will not be published. Required fields are marked <span class="required">*</span></p>',
			'comment_field'        => '<p class="comment-form-comment"><label for="comment">Comment <span class="required">*</span></label><textarea id="comment" name="comment" cols="45" rows="6" required></textarea></p>',
			'fields'               => array(
				'author' => '<p class="comment-form-author"><label for="author">Name <span class="required">*</span></label><input id="author" name="author" type="text" value="" size="30" required></p>',
				'email'  => '<p class="comment-form-email"><label for="email">Email <span class="required">*</span></label><input id="email" name="email" type="email" value="" size="30" required></p>',
				'url'    => '<p class="comment-form-url"><label for="url">Website</label><input id="url" name="url" type="url" value="" size="30"></p>',
			),
		)
	);
	?>

</div><!-- #comments -->
