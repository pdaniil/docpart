<?php

	require_once('DocpartTable.php');
	class DocpartContent extends DocpartTable
	{
		public $url;
		public $alias;
		public $value;
		public $description;
		public $is_frontend;
		public $content_type;
		public $content;
		public $title_tag;
		public $description_tag;
		public $keywords_tag;
		public $author_tag;
		public $mail_flag;
		public $modules_array;
		public $css_js;
		public $robots_tag;
		public $system_flag;
		public $published_flag;
		public $open;
		public $time_created;
		public $time_edited;
		public $order;
	}

?>