<?php  
	/* 
	Plugin Name: Export Comment Authors
	Plugin URI: http://www.brianjlink.com/export-comment-authors
	Description: Export Commenter Information (Name, Email, etc.) to a CSV File.
	Author: Brian J. Link 
	Version: 1.3 
	Author URI: http://www.brianjlink.com
	*/
	
	add_action('admin_menu', 'bjl_cexport_add_menu');
	add_action('manage_comments_nav', 'bjl_cexport_comments_nav');
	add_action('admin_head', 'bjl_cexport_style_admin');
	
	if ($_GET["bjl_cexport"] == true)
	{
		if ($_GET["bjl_cexport_post"] != 0) $bjl_cexport_filter = " comment_post_ID = ".$_GET["bjl_cexport_post"]." AND ";
		
		bjl_cexport_csv($bjl_cexport_filter);
	}
	
	function bjl_cexport_admin()
	{
		global $wpdb, $table_prefix;
		
		$bjl_cexport_counter = 0;
		$bjl_cexport_start = 0;
		$bjl_cexport_limit = 25;
		
		if ($_GET["bjl_cexport_post"] != 0)
		{
			$post = get_post($_GET["bjl_cexport_post"]);
			
			$bjl_cexport_filter = " comment_post_ID = ".$_GET["bjl_cexport_post"]." AND ";
			$bjl_cexport_aname =  __("Export '").bjl_cexport_trim_string($post->post_title).__("' Commenters");
		}
		else
		{
			$bjl_cexport_aname = __("Export All Commenters");
		}
		
		if ($_GET['pagenum'] > 1) { $bjl_cexport_start = ($_GET['pagenum'] - 1) * $bjl_cexport_limit; }
		
		echo '<div class="wrap">';
		echo '<div id="icon-users" class="icon32"></div><h2>'.__("Export Comment Authors").'</h2>';
		
		// CAPTURE COMMENTER INFORMATION
		$bjl_cexport_commenters = $wpdb->get_results("SELECT SQL_CALC_FOUND_ROWS COUNT(comment_ID) AS count, comment_ID, comment_author, comment_author_email, comment_author_url, comment_date FROM $table_prefix"."comments WHERE".$bjl_cexport_filter." comment_approved = 1 AND comment_type = '' GROUP BY comment_author, comment_author_email ORDER BY comment_author ASC, comment_date DESC LIMIT ".$bjl_cexport_start.", ".$bjl_cexport_limit.";");
		
		// CAPTURE COMMENTERS COUNT
		$bjl_cexport_commenters_count = $wpdb->get_var("SELECT FOUND_ROWS();");
		
		if ($bjl_cexport_commenters_count > 0)
		{
			echo '<h3>'.$bjl_cexport_commenters_count.' '.__("Authors Found").'</h3>';

			echo '<form id="bjl_cexport-filter" action="'.admin_url('edit-comments.php').'" method="get">';
			echo '<input type="hidden" name="page" value="export-comment-authors.php" />';
			
			echo '<div class="tablenav">';
			echo '<div class="alignleft actions">';

			echo '<select name="bjl_cexport_post">';
			echo '<option value="0">'.__("All Posts & Pages").'</option>';
			echo '<optgroup label="'.__("Recent Posts with Comments").'">';
			
			// LAST 20 POSTS WITH COMMENTS
			$bjl_cexport_posts = $wpdb->get_results("SELECT ID, post_title, comment_count FROM $table_prefix"."posts WHERE post_type = 'post' AND post_status = 'publish' AND comment_count > 0 ORDER BY post_date DESC LIMIT 0, 20;");
			foreach ($bjl_cexport_posts as $bjl_cexport_post)
			{
				echo '<option value="'.$bjl_cexport_post->ID.'"'.($_GET["bjl_cexport_post"] == $bjl_cexport_post->ID ? " selected" : "").'>'.bjl_cexport_trim_string($bjl_cexport_post->post_title).'</option>';
			}
			echo '</optgroup>';
			
			// PAGES WITH COMMENTS
			$bjl_cexport_pages = $wpdb->get_results("SELECT ID, post_title, comment_count FROM $table_prefix"."posts WHERE post_type = 'page' AND post_status = 'publish' AND comment_count > 0 ORDER BY post_title ASC");
			if (count($bjl_cexport_pages) > 0)
			{
				echo '<optgroup label="'.__("Pages with Comments").'">';
				foreach ($bjl_cexport_pages as $bjl_cexport_page)
				{
					echo '<option value="'.$bjl_cexport_page->ID.'"'.($_GET["bjl_cexport_post"] == $bjl_cexport_page->ID ? " selected" : "").'>'.bjl_cexport_trim_string($bjl_cexport_page->post_title).'</option>';
				}
				echo '</optgroup>';
			}
				
			echo '</select> ';
			
			echo '<input type="submit" id="post-query-submit" value="'.__("Filter").'" class="button-secondary" /> ';
			
			echo '<a class="button-primary" href="?page='.basename(__FILE__).'&bjl_cexport=true&bjl_cexport_post='.$_GET["bjl_cexport_post"].'" title="'.esc_attr($bjl_cexport_aname).'">'.esc_attr($bjl_cexport_aname).'</a>';
			echo '</div>';
			
			// PAGINATION CODE - MODIFIED FROM wp-admin/edit-pages.php
			$pagenum = isset($_GET['pagenum']) ? absint($_GET['pagenum']) : 0;
			if (empty($pagenum)) $pagenum = 1;
			$per_page = $bjl_cexport_limit;
			$num_pages = ceil($bjl_cexport_commenters_count / $per_page);
			
			$page_links = paginate_links(array(
				'base' => add_query_arg('pagenum', '%#%'),
				'format' => '',
				'prev_text' => __('&laquo;'),
				'next_text' => __('&raquo;'),
				'total' => $num_pages,
				'current' => $pagenum
			));
			
			if ($page_links)
			{
			?>

				<div class="tablenav-pages">
				<?php $page_links_text = sprintf('<span class="displaying-num">'.__('Displaying %s&#8211;%s of %s').'</span>%s',
					number_format_i18n(($pagenum - 1) * $per_page + 1),
					number_format_i18n(min($pagenum * $per_page, $bjl_cexport_commenters_count)),
					number_format_i18n($bjl_cexport_commenters_count),
					$page_links);
					
					echo $page_links_text;
				?>
				</div>
				<div class="clear"></div>
			<?
			}
			// PAGINATION CODE - END
			
			echo '</div>';
			echo '<div class="clear"></div>';
			echo '</form>';
			
			echo '<table class="widefat post fixed" cellspacing="0">';
			echo '<thead>';
			echo '<tr>';
			echo '<th scope="col" id="author">'.__("Author").'</th>';
			echo '<th scope="col" id="email">'.__("Email Address").'</th>';
			echo '<th scope="col" id="url">'.__("URL").'</th>';
			echo '<th scope="col" id="date" class="manage-column column-date">'.__("Date").'</th>';
			echo '<th scope="col" id="comments" class="manage-column column-comments num"><div class="vers"><img alt="'.__("Comments").'" src="images/comment-grey-bubble.png" /></div></th>';
			echo '</tr>';
			echo '</thead>';
			
			echo '<tfoot>';
			echo '<tr>';
			echo '<th scope="col" id="author">'.__("Author").'</th>';
			echo '<th scope="col" id="email">'.__("Email Address").'</th>';
			echo '<th scope="col" id="url">'.__("URL").'</th>';
			echo '<th scope="col" id="date" class="manage-column column-date">'.__("Date").'</th>';
			echo '<th scope="col" id="comments" class="manage-column column-comments num"><div class="vers"><img alt="'.__("Comments").'" src="images/comment-grey-bubble.png" /></div></th>';
			echo '</tr>';
			echo '</tfoot>';
			
			echo '<tbody>';
			foreach ($bjl_cexport_commenters as $bjl_cexport_commenter)
			{
				echo '<tr'.($bjl_cexport_counter % 2 == 1 ? "" : " class='alternate'").'>';
				echo '<td><strong>'.$bjl_cexport_commenter->comment_author.'</strong></td>';
				echo '<td><a href="mailto:'.$bjl_cexport_commenter->comment_author_email.'">'.$bjl_cexport_commenter->comment_author_email.'</a></td>';
				echo '<td><a target="_new" href="'.$bjl_cexport_commenter->comment_author_url.'">'.$bjl_cexport_commenter->comment_author_url.'</a></td>';
				echo '<td class="date column-date">'.mysql2date(__('Y/m/d'), $bjl_cexport_commenter->comment_date).'</td>';
				echo '<td style="width:75px; text-align:center;"><div class="post-com-count-wrapper"><a class="post-com-count"><span class="comment-count">'.$bjl_cexport_commenter->count.'</span></a></div></td>';
				echo '</tr>';
				
				$bjl_cexport_counter++;
			}
			echo '</tbody>';
			echo '</table>';
		}
		else
		{
			echo '<p>'.__("No Commenters Found.").'</p>';
		}
		
		// SHAMELESS PLUGS
		echo '<div id="bjl_shameless_plugs">';
		echo '<h3><a href="http://www.brianjlink.com/wordpress-plugins/">More Plugins from Brian J. Link</a></h3>';
		
		echo '<ul>';
		echo '<li class="buffer">';
		echo '<a href="http://www.brianjlink.com/wpwordcount/"><img src="'.get_option('siteurl').'/wp-content/plugins/'.basename(dirname(__FILE__)).'/images/wpwordcount.jpg'.'" alt="WP Word Count" title="WP Word Count" /></a>';
		echo '<div><a href="http://www.brianjlink.com/wpwordcount/">WP Word Count</a></div>';
		echo 'WP Word Count is a plugin for WordPress that gives you word count statistics for your blog\'s posts and pages.';
		echo '</li>';
		
		echo '<li>';
		echo '<a href="http://www.brianjlink.com/flicknpress/"><img src="'.get_option('siteurl').'/wp-content/plugins/'.basename(dirname(__FILE__)).'/images/flicknpress.jpg'.'" alt="flicknpress" title="flicknpress" /></a>';
		echo '<div><a href="http://www.brianjlink.com/flicknpress/">flicknpress</a></div>';
		echo 'flicknpress is a WordPress plugin that lets you attach a cropped photo from Flickr right inside your blog post.';
		echo '</li>';
		echo '</ul>';
		
		echo '</div>';
				
		echo '</div>';	// END DIV.WRAP
	}
	
	function bjl_cexport_csv($bjl_cexport_filter)
	{
		// WRITE CSV AND PUSH TO BROWSER
		global $wpdb, $table_prefix;
		
		$line = bjl_cexport_escape_comma(__("Commenter")).", ";
		$line.= bjl_cexport_escape_comma(__("Email Address")).", ";
		$line.= bjl_cexport_escape_comma(__("URL")).", ";
		$line.= bjl_cexport_escape_comma(__("Date")).", ";
		$line.= bjl_cexport_escape_comma(__("IP Address")).", ";
		$line.= bjl_cexport_escape_comma(__("Comments"))."\n";
		
		// CAPTURE ALL COMMENTER INFORMATION
		$bjl_cexport_commenters = $wpdb->get_results("SELECT COUNT(comment_ID) AS count, comment_ID, comment_author, comment_author_email, comment_author_url, comment_date, comment_author_IP FROM $table_prefix"."comments WHERE".$bjl_cexport_filter." comment_approved = 1 AND comment_type = '' GROUP BY comment_author, comment_author_email ORDER BY comment_author ASC, comment_date DESC;");
		
		foreach ($bjl_cexport_commenters as $bjl_cexport_commenter)
		{
			$line.= bjl_cexport_escape_comma($bjl_cexport_commenter->comment_author).", ";
			$line.= bjl_cexport_escape_comma($bjl_cexport_commenter->comment_author_email).", ";
			$line.= bjl_cexport_escape_comma($bjl_cexport_commenter->comment_author_url).", ";
			$line.= bjl_cexport_escape_comma(mysql2date(__('Y/m/d'), $bjl_cexport_commenter->comment_date)).", ";
			$line.= bjl_cexport_escape_comma($bjl_cexport_commenter->comment_author_IP).", ";
			$line.= bjl_cexport_escape_comma($bjl_cexport_commenter->count)."\n";
		}
		
		header('Content-type: application/csv');
		header("Content-Disposition: inline; filename=commenter-export".date("Ymd").".csv");
		header('Pragma: no-cache');
		echo $line;
		exit();
	}
	
	function bjl_cexport_escape_comma($value)
	{
		$value = str_replace('"', '""', $value);
		
		if(preg_match('/,/', $value) or preg_match("/\n/", $value) or preg_match('/"/', $value))
		{
			return '"'.$value.'"';
		}
		else
		{
			return $value;
		}
	}
	
	function bjl_cexport_filesize($filename)
	{
		$size = filesize($filename);
		$sizes = array(" Bytes", " KB", " MB", " GB", " TB", " PB", " EB", " ZB", " YB");
		
		if ($size == 0)
		{
			return "N/A";
		}
		else
		{
			return round($size/pow(1024, ($i = floor(log($size, 1024)))), 2).$sizes[$i];
		}
	}
	
	function bjl_cexport_trim_string($string)
	{
		if (strlen($string) > 30)
		{
			$string = trim(substr($string, 0, 30))."...";
		}
		
		return $string;
	}
	
	function bjl_cexport_add_menu()
	{
		add_comments_page(__("Export Comment Authors"), __("Export Authors"), 1, basename(__FILE__), 'bjl_cexport_admin');
	}
	
	function bjl_cexport_comments_nav()
	{
		if ($_GET["p"])
		{
			echo '</div>';
			echo '<div class="alignleft">';
			echo '<a class="button-secondary checkforspam" href="?page='.basename(__FILE__).'&bjl_cexport=true&bjl_cexport_post='.$_GET["p"].'" title="'.__("Export Authors").'">'.__("Export Authors").'</a>';
		}
	}
	
	function bjl_cexport_style_admin()
	{
		$siteurl = get_option('siteurl');
		$url = get_option('siteurl').'/wp-content/plugins/'.basename(dirname(__FILE__)).'/style_admin.css';
		
		echo "<link rel='stylesheet' type='text/css' href='$url' />\n";
	}
?>