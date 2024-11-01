<?php
/*
Plugin Name: Verse of the Day
Version: 3.5 beta
Plugin URI: http://blog.slaven.net.au/wordpress-plugins/wordpress-verse-of-the-day-plugin/
Description: Places the bible 'Verse of the Day' on your site.  Defaults to the ESV feed provided by Good News Publishers, but can accept any feed url. <strong>Requires WordPress 2.2</strong>
Author: Glenn Slaven
Author URI: http://blog.slaven.net.au/

	Copyright 2004  Glenn Slaven  (email : gdalziel@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/
include_once (ABSPATH . WPINC . "/rss-functions.php");
include_once ('plugin-base.php');

if (!class_exists('wp_votd') && class_exists('plugin_base')) {
	class wp_votd extends plugin_base {

		var $name = 'Verse of the Day';
		var $filename = __FILE__;

		function wp_votd() {
			parent::plugin_base();
			
			add_action('wp_votd_update_contents', array(&$this, 'update_contents'));
			add_action('widgets_init', array(&$this, 'widget_init'));			
		}
		
		function _install() {
			wp_schedule_event(0, 'daily', 'wp_votd_update_contents' );
			$this->set_options('RESET');
			$this->update_contents();
		}
		
		function _uninstall() {
			delete_option('wp_votd_options');
			delete_option('wp_votd_cache');
			remove_action('wp_votd_update_contents', 'wp_votd_update_contents');
			wp_clear_scheduled_hook('wp_votd_update_contents');
		}

		function widget_init() {
			if ( !function_exists('register_sidebar_widget') || !function_exists('register_widget_control') ) return;
				
			register_sidebar_widget(array('Verse of the Day', 'widgets'), array(&$this, 'show_widget'));
			register_widget_control(array('Verse of the Day', 'widgets'), array(&$this, 'widget_control'), 300, 80);
		}
		
		function show_widget($args) {
			extract($args);
			$options = get_option('wp_votd_options'); 
			echo $before_widget;
			echo $before_title . $options['wp_votd_title'] . $after_title;
			echo get_option('wp_votd_cache');
			echo $after_widget;
		}
		
		function widget_control() {
			$options = get_option('wp_votd_options'); 
			if ( $_POST["votd-submit"] ) { 
				$new_title = strip_tags(stripslashes($_POST["votd-title"]));
				if ( $options['wp_votd_title'] != $new_title ) {
					$this->set_options('UPDATE', false, false, false, false, false, $new_title);
					$options['wp_votd_title'] = $new_title;
				}
			}
			$title = attribute_escape($options['wp_votd_title']);
?>
			<p><label for="votd-title"><?php _e('Title:'); ?> <input style="width: 250px;" id="votd-title" name="votd-title" type="text" value="<?php echo $title; ?>" /></label></p>
			<p><input style="width: 200px;" id="votd-buttontext" name="votd-buttontext" type="submit" value="Update" /></p>
					<input type="hidden" id="votd-submit" name="votd-submit" value="1" />
<?php
		}
		
		function set_options($action, $version = false, $url = false, $name = false, $timeout = false, $template = false, $title = false)  {

			$options = array(
				'wp_votd_url' 				=> 'http://www.gnpcb.org/esv/share/rss2.0/daily/',
				'wp_votd_version'			=> 'ESV',
				'wp_votd_name'				=> 'English Standard Version',
				'wp_votd_template'			=> '<p id="votd">[TEXT] (<a href="[LINK]">[TITLE]</a>[VERSION])[ENCLOSURE]</p>',
				'wp_votd_title'				=> 'Verse of the Day'
			);

			if ('RESET' == $action) {
				update_option('wp_votd_options', $options);
			} elseif ('UPDATE' == $action) {
				$options = get_option('wp_votd_options');

				if ($version) { $options['wp_votd_version'] = $version; }
				if ($url) { $options['wp_votd_url'] = $url; }
				if ($name) { $options['wp_votd_name'] = $name; }
				if ($template) { $options['wp_votd_template'] = stripslashes($template); }
				if ($title) { $options['wp_votd_title'] = strip_tags(stripslashes($title)); }
				
				if (is_array($options)) {
					update_option('wp_votd_options', $options);
					$this->update_contents();
					return $this->get_feed(true);
				} else {
					return false;
				}
			}
		}

		function options_page() {

			//Set the default feeds
			$VOTD_DEFAULT_FEEDS = array(
				'ESV'  => array('url' => 'http://www.gnpcb.org/esv/share/rss2.0/daily/',           'name' => 'English Standard Version'),
				'NIV'  => array('url' => 'http://www.biblegateway.com/usage/votd/rss/votd.rdf?31', 'name' => 'New International Version'),
				'KJV'  => array('url' => 'http://www.biblegateway.com/usage/votd/rss/votd.rdf?9',  'name' => 'King James Version')
			);

			$post_vars = ($_POST ? $_POST : get_option('wp_votd_options'));

			if (isset($_POST['wp_votd_update'])) {
			    $this->set_options('UPDATE',
								    (strlen($_POST['wp_votd_version']) ? $_POST['wp_votd_version'] : $_POST['wp_votd_other_version']),
							        (strlen($_POST['wp_votd_version']) ? $VOTD_DEFAULT_FEEDS[$_POST['wp_votd_version']]['url'] : $_POST['wp_votd_other_url']),
									(strlen($_POST['wp_votd_version']) ? $VOTD_DEFAULT_FEEDS[$_POST['wp_votd_version']]['name'] : $_POST['wp_votd_other_name']),
									$_POST['wp_votd_timeout'],
									$_POST['wp_votd_template']);
			} elseif (FALSE === get_option('wp_votd_options') || '' === get_option('wp_votd_options')) {
				$this->set_options('RESET');
			}

			$options = get_option('wp_votd_options');
			$cache_contents = $this->get_feed();

		?>
		<div class=wrap>
		 <form method="post">
		 <input type="hidden" name="wp_votd_update" value="true" />
		  <h2>Verse of the Day Options</h2>
		  <blockquote style="font-style:italic;">All Scripture is breathed out by God and profitable for teaching, for reproof, for correction, and for training in righteousness, that the man of God may be competent, equipped for every good work. (<a href="http://www.gnpcb.org/esv/search/?q=2+Timothy+3%3A16-17">2 Timothy 3:16-17</a>, <abbr title="English Standard Version">ESV</abbr>)</blockquote>
		  <fieldset class="options">
		  <legend>Select a bible version to use</legend>
		  <p>You can enter the details of a different Verse of the day RSS feed if you want.  If you enter the feed details in manualy, it will over-ride a selection in the dropdown list.</p>
		  <p>After selecting your options then clicking 'Update' put &lt;?php wp_votd(); ?&gt; on your template file where you want the verse to display. See the <a href="http://blog.slaven.net.au/wordpress-plugins/wordpress-verse-of-the-day-plugin/">Verse of the Day instructions page</a> for more information</p>
		  <table width="100%" cellspacing="2" cellpadding="5" class="editform">
		  <tr>
		   <th width="33%" scope="row" valign="top">Select version:</th>
		   <td><select id="wp_votd_version" name="wp_votd_version">
		<?php
			$versions = array_keys($VOTD_DEFAULT_FEEDS);
			foreach($versions as $v) {
				print "<option value=\"$v\"".($options['wp_votd_version'] == $v ? ' selected="selected"' : '').">{$VOTD_DEFAULT_FEEDS[$v]['name']}</option>\n";
			}
		?>
			 <option value=""<?=(array_key_exists($options['wp_votd_version'], $VOTD_DEFAULT_FEEDS) ? '' : ' selected="selected"')?>>Other...</option>
			</select>
		   </td>
		  </tr>
		  <tr>
		   <th width="33%" scope="row" valign="top"><strong style="font-size:larger; color: #FF1100">or</strong></th>
		   <td></td>
		  </tr>
		  <tr>
		   <th width="33%" scope="row" valign="top">Version Abbreviation (e.g. ESV, NIV, etc...):</th>
		   <td><input style="width:3em;" type="text" name="wp_votd_other_version" id="wp_votd_other_version" value="<?=(array_key_exists($options['wp_votd_version'], $VOTD_DEFAULT_FEEDS) ? '' : $options['wp_votd_version'])?>" /></td>
		  </tr>
		  <tr>
		   <th width="33%" scope="row" valign="top">Version Name:</th>
		   <td><input style="width:20em;" type="text" name="wp_votd_other_name" id="wp_votd_other_name" value="<?=(array_key_exists($options['wp_votd_version'], $VOTD_DEFAULT_FEEDS) ? '' : $options['wp_votd_name'])?>" /></td>
		  </tr>
		  <tr>
		   <th width="33%" scope="row" valign="top">RSS feed URL:</th>
		   <td><input style="width:30em;" type="text" name="wp_votd_other_url" id="wp_votd_other_url" value="<?=(array_key_exists($options['wp_votd_version'], $VOTD_DEFAULT_FEEDS) ? '' : $options['wp_votd_url'])?>" /></td>
		  </tr>
		  </table>
		  </fieldset>
		  <fieldset class="options">
		  <legend>Customization</legend>
		  <table width="100%" cellspacing="2" cellpadding="5" class="editform">
		  <tr>
		   <th scope="row" width="33%" valign="top">Display Template:<div style="font-weight:normal;">Changing this will change how the verse displays on your site.  Use it to set class/id tags to style the verse using CSS.</div></th>
		   <td><textarea rows="3" cols="55" name="wp_votd_template" id="wp_votd_template"><?=$options['wp_votd_template']?></textarea></td>
		  </tr>
		<?php
		if ($show_block) {
		?>
		  <tr>
		   <th width="33%" scope="row" valign="top">Create a <a href="http://warpspire.com/hemingway">Hemingway</a> block:<?=($block_error ? '<div style="color:#FF0000; font-weight: bold;">'.$block_error.'</div>' : '')?></th>
		   <td>
		   <input type="checkbox" name="wp_votd_create_block" id="wp_votd_create_block" value="1"<?=($options['wp_votd_create_block'] ? ' checked="checked"' : '')?> />
		   </td>
		  </tr>
		<?php
		  }
		?>
		  <tr>
		   <th scope="row" width="33%">Display Output:</th>
		   <td><?=$cache_contents?></td>
		  </tr>
		  </table>
		  </fieldset>
		  <div class="submit"><input type="submit" name="info_update" value="<?php _e('Update') ?> &raquo;" /></div>
		 </form>
			<div style="background-color:rgb(238, 238, 238); border: 1px solid rgb(85, 85, 85); padding: 5px; margin-top:10px;">
			<p>Did you find this plugin useful?  Please consider donating to help me continue developing it and other plugins.</p>
		<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
		<input type="hidden" name="cmd" value="_xclick">
		<input type="hidden" name="business" value="paypal@slaven.net.au">
		<input type="hidden" name="item_name" value="Verse of the Day Wordpress Plugin">
		<input type="hidden" name="no_note" value="1">
		<input type="hidden" name="currency_code" value="AUD">
		<input type="hidden" name="tax" value="0">
		<input type="hidden" name="bn" value="PP-DonationsBF">
		<input type="image" src="https://www.paypal.com/en_US/i/btn/x-click-but04.gif" border="0" name="submit" alt="Make payments with PayPal - it's fast, free and secure!">
		</form></div>
		</div>
		<?php
		}	
		
		function get_feed() {
			return get_option('wp_votd_cache');
		}
		
		function update_contents() {
			$options = get_option('wp_votd_options');
		
			if ($options['wp_votd_url'] && class_exists('Snoopy') && class_exists('MagpieRSS')) {

				//Grab the url & parse
				$client = new Snoopy();
				$client->read_timeout = 3;
				$client->use_gzip = true;
				@$client->fetch($options['wp_votd_url']);
				if ($client->results) {
					$rss = new MagpieRSS($client->results);
					if ($rss && is_array($rss->items) && count($rss->items) > 0) {

						$enclosure = preg_match('/\<enclosure.*?url="(.*?)".*?\>/i',$client->results, $matches);
						$enclosure = $matches[1];
						$enclosure = str_replace("&","&amp;",$enclosure);

			            //Pull content out of the feed
						$verse_title = $rss->items[0]['title'];
						$verse_body = ($rss->items[0]['content']['encoded'] ? $rss->items[0]['content']['encoded'] : $rss->items[0]['description']);
						$verse_link = ($rss->items[0]['guid'] ? $rss->items[0]['guid'] : $rss->items[0]['link']);
						$verse_link = str_replace("&","&amp;",$verse_link);
						$verse_version = $options['wp_votd_version'];
						$version_name = $options['wp_votd_name'];

						//Insert verse into template
						$content = preg_replace(
							array(
								'/\[TEXT\]/',
								'/\[TITLE\]/',
								'/\[LINK\]/',
								'/\[VERSION\]/',
								'/\[ENCLOSURE\]/'
							),
							array(
								$verse_body,
								$verse_title,
								$verse_link,
								($verse_version ? ", <abbr title=\"{$options['wp_votd_name']}\">{$options['wp_votd_version']}</abbr>" : ''),
								($enclosure ? " <span class=\"votdenclosure\">(<a href=\"$enclosure\">Listen</a>)</span>" : '')
							),
							$options['wp_votd_template']
						);

						update_option('wp_votd_cache', $content);
						update_option('wp_votd_lastcache_time', time());

						return $content;
					} else {
						if ($_GET['dieloud']) { print "<span style=\"color:#FF0000;\"><strong>VOTD Plugin Error</strong><br />Sorry, the script cannot understand the feed from '<a href=\"{$options['wp_votd_url']}\">{$options['wp_votd_url']}</a>'.  Please check that this is a valid RSS feed.</span>\n"; }
					}
				} else {
					if ($_GET['dieloud']) { print "<span style=\"color:#FF0000;\"><strong>VOTD Plugin Error</strong><br />Sorry, the script was unable to retrieve anything from '<a href=\"{$options['wp_votd_url']}\">{$options['wp_votd_url']}</a>'.  Please check that URL to ensure it is available.</span>\n"; }
				}
			}	
		}
	}
}

$wp_votd = new wp_votd();

function wp_votd() {
	global $wp_votd;
	print $wp_votd->get_feed();
}

?>