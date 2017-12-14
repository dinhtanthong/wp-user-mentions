<h2><?php printf(__('Hi %s', 'user-mentions'), $user->display_name); ?>,</h2>

<p>
	<?php printf(__('%s has mentioned you in a comment on', 'user-mentions'), $comment->comment_author); ?>
	<a href="<?php echo get_permalink($comment->comment_post_ID); ?>"><?php echo get_the_title($comment->comment_post_ID); ?></a>
</p>

<?php echo $comment->comment_content; ?>

<p><a href="<?php echo $comment_link; ?>"><?php echo __('Click here to reply', 'user-mentions'); ?></a></p>