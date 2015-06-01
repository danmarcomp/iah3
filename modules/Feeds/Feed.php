<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');
/*********************************************************************************
 * The contents of this file are subject to the SugarCRM Public License Version
 * 1.1.3 ("License"); You may not use this file except in compliance with the
 * License. You may obtain a copy of the License at http://www.sugarcrm.com/SPL
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied.  See the License
 * for the specific language governing rights and limitations under the
 * License.
 *
 * All copies of the Covered Code must include on each user interface screen:
 *    (i) the "Powered by SugarCRM" logo and
 *    (ii) the SugarCRM copyright notice
 * in the same form as they appear in the distribution.  See full license for
 * requirements.
 *
 * The Original Code is: SugarCRM Open Source
 * The Initial Developer of the Original Code is SugarCRM, Inc.
 * Portions created by SugarCRM are Copyright (C) 2004-2006 SugarCRM, Inc.;
 * All Rights Reserved.
 * Contributor(s): ______________________________________.
 ********************************************************************************/
/*********************************************************************************

 * Description:  TODO: To be written.
 * Portions created by SugarCRM are Copyright (C) SugarCRM, Inc.
 * All Rights Reserved.
 * Contributor(s): ______________________________________..
 ********************************************************************************/



require_once('data/SugarBean.php');

// Contact is used to store customer information.
class Feed extends SugarBean {
	
	// Stored fields
	var $id;
	var $date_entered;
	var $date_modified;
	var $modified_user_id;
	var $assigned_user_id;
	var $created_by;
	var $created_by_name;
	var $modified_by_name;


	var $url;
	var $title;
	var $description;
	var $content;
	var $favorite = false;
	var $user_id;

	var $my_favorites = false;
	var $feed_obj;

	var $table_name = "feeds";
	var $object_name = "Feed";
	var $module_dir = 'Feeds';
	var $rel_users_feeds = "users_feeds";
	var $new_schema = true;

	var $additional_column_fields = array();


	function Feed() {
		parent::SugarBean();
	}

	function move ($dir='up',$feed_id,$user_id)
	{
		$user_id = $this->db->quote($user_id);


		$query = "SELECT rank from {$this->rel_users_feeds} where user_id='$user_id' AND feed_id='$feed_id' AND deleted=0 order by rank";

//print ">SD:".$query;
		$result = $this->db->query($query, -1);
		$feeds = array();

		$feed_at = -1;
                $row =  $this->db->fetchByAssoc($result, -1);
		if ( empty($row))
		{
			sugar_die("feed_id not found:".$feed_id);
		}

		if ($dir == 'up')
		{
			if ( $row['rank'] <= 1)
			{
				return;
			}

			$oldotherrank = $row['rank'] - 1;
			$newotherrank = $row['rank'];
			$oldrank = $row['rank'] ;
			$newrank = $oldrank - 1;
		}
		else
		{
			$query = "SELECT count(*) as count from {$this->rel_users_feeds} where user_id='$user_id' AND deleted=0 order by rank";

			$result = $this->db->query($query, -1);

                	$countrow =  $this->db->fetchByAssoc($result, -1);

			$count = $countrow['count'];

			if ( $row['rank'] >=$count)
			{
				return;
			}

			$oldotherrank = $row['rank'] + 1;
			$newotherrank = $row['rank'];
			$oldrank = $row['rank'] ;
			$newrank = $oldrank + 1;
		}

		$query = "update {$this->rel_users_feeds} set rank=$newotherrank where user_id='$user_id' AND rank=$oldotherrank AND deleted=0";
		$this->db->query($query);

		$query = "update {$this->rel_users_feeds} set rank=$newrank where user_id='$user_id' AND feed_id='$feed_id' AND deleted=0";

		$this->db->query($query);
	}


	function addToFavorites ($feed_id,$user_id)
	{
		$user_id = $this->db->quote($user_id);

		$query = "SELECT max(rank) as maxrank from {$this->rel_users_feeds} where user_id='$user_id' AND  deleted=0";
		$result = $this->db->query($query, -1);
                $row =  $this->db->fetchByAssoc($result, -1);
		if ($row['maxrank'] == 0)
                {
		$rank = 1;
		}
		else
		{
		$rank = $row['maxrank'] + 1;
		}

		$query = "SELECT deleted from {$this->rel_users_feeds} where user_id='$user_id' AND feed_id='$feed_id' and deleted=0";
		$result = $this->db->query($query, -1);
                $row =  $this->db->fetchByAssoc($result, -1);


		if (empty($row))
		{
		
			$query = "insert into {$this->rel_users_feeds} (user_id, feed_id, rank, deleted) VALUES( '$user_id','$feed_id',$rank,0 )";
			$this->db->query($query);
		}
		else if ( ! empty($row) && $row['deleted'] == 1)
		{
			$query = "update {$this->rel_users_feeds} set deleted=0,rank=$rank where  user_id='$user_id' AND feed_id='$feed_id'";
			$this->db->query($query);
		}
	}

	function removeFavorites ($feed_id,$user_id)
	{
		$user_id = $this->db->quote($user_id);
		$query = "SELECT deleted,rank from {$this->rel_users_feeds} where user_id='$user_id' AND feed_id='$feed_id'";
		$result = $this->db->query($query, -1);

                $row =  $this->db->fetchByAssoc($result, -1);

		if ( isset($row) && $row['deleted'] == 0)
		{
			$query = "delete from {$this->rel_users_feeds} where  user_id='$user_id' AND feed_id='$feed_id'";
			$this->db->query($query);

			if(isset($row['rank'])) {
				$query = "update {$this->rel_users_feeds} set rank=rank-1 where rank > {$row['rank']} AND  user_id='$user_id' AND deleted=0";
				$this->db->query($query);
			}
		}


	}

	function createRSSHomePage($user_id)
	{
//from feeds.sql:

		$this->addToFavorites('4bbca87f-2017-5488-d8e0-41e7808c2553',$user_id) ;
	}


	/**
	 * fills DB with demo RSS feeds for seed data
	 */
	static function populate_feeds() {
		global $db;
		$lines = file('modules/Feeds/feeds_os.sql');
		foreach ($lines as $line) {
			$line = chop($line);
			$db->query($line);
		}
	}

	function get_summary_text()
	{
		return $this->title;
	}

        function add_list_count_joins(&$query, $where)
        {
		global $current_user;
                $query .= " LEFT JOIN  {$this->rel_users_feeds} ";
                $query  .= " ON ( {$this->rel_users_feeds}.user_id IS NULL                                 OR {$this->rel_users_feeds}.deleted=0 )
                        AND  {$this->rel_users_feeds}.user_id='{$current_user->id}'
                        AND {$this->rel_users_feeds}.feed_id={$this->table_name}.id ";
         }

	function create_list_query($order_by, $where, $show_deleted = 0)
	{
		global $current_user;

		$query = "SELECT {$this->table_name}.*, {$this->rel_users_feeds}.user_id AS favorite";
	        $query .= " FROM {$this->table_name} ";
		$query .= " LEFT JOIN  {$this->rel_users_feeds} ";
		$query  .= " ON ( {$this->rel_users_feeds}.user_id IS NULL OR {$this->rel_users_feeds}.deleted=0 ) AND  {$this->rel_users_feeds}.user_id='{$current_user->id}' AND {$this->rel_users_feeds}.feed_id={$this->table_name}.id ";

		$where_auto = '1=1';
		if($show_deleted == 0){
			$where_auto = " {$this->table_name}.deleted=0 ";
		}else if($show_deleted == 1){
			$where_auto = " {$this->table_name}.deleted=1 ";
		}
			

		if($where != "")
			$query .= "where ($where) AND ".$where_auto;
		else
			$query .= "where ".$where_auto;

		if(!empty($order_by))
			$query .= " ORDER BY $order_by";
		return $query;
	}

  function create_export_query($order_by, $where)   
	{
    $query = "SELECT         feeds.*";
    $query .= " FROM feeds ";

    $where_auto = " feeds.deleted = 0";

    if($where != "")       
		{
			$query .= " WHERE $where AND " . $where_auto;
		}
    else       
		{
			$query .= " WHERE " . $where_auto;
		}
    if($order_by != "")
		{
      $query .= " ORDER BY $order_by";     
		}

    return $query;
  }


	function save($check_notify=false)
	{
		if(empty($this->title) && $this->load_feed()) {
			$this->title = $this->feed_obj->get_title();
		}
		$ret = parent::save();
	}



	function fill_in_additional_list_fields()
	{
		//$this->fill_in_additional_detail_fields();
	}

	function fill_in_additional_detail_fields()
	{
		/*global $current_user;

		$query = "select user_id from {$this->rel_users_feeds} where user_id='{$current_user->id}' AND feed_id='{$this->id}' AND deleted=0";

                $result = $this->db->query($query, -1);

                $row =  $this->db->fetchByAssoc($result, -1);

                if (! empty($row))
                {
		 	$this->favorite = true;
		}*/

	}

	function get_list_view_data()
	{
		global $image_path;
		global $mod_strings;
		$temp_array = $this->get_list_view_array();

		/*if ( $this->my_favorites )
		{
			$view = '';
		}
		else
		{
			$view = '&view=all';
		}

		if ( empty($temp_array['FAVORITE']))
		{
    		$temp_array['FAVORITE']= "<a href=\"index.php?return_action=".$_REQUEST['action']."&return_module=Feeds&action=AddFavorite&module=Feeds&record=".$temp_array['ID']."$view\" class=\"listViewTdToolsS1\">".get_image($image_path."plus_inline",'border="0" align="absmiddle" alt="'.$mod_strings['LBL_ADD_FAV_BUTTON_LABEL'].'"')."</a>&nbsp;<a href=\"index.php?return_action=".$_REQUEST['action']."&return_module=Feeds&action=AddFavorite&module=Feeds&record=".$temp_array['ID']."$view\" class=\"listViewTdToolsS1\">".$mod_strings['LBL_ADD_FAV_BUTTON_LABEL']."</a>";
		}
		else
		{

		//	if (! $this->my_favorites)
		//	{
    				$temp_array['ASTERISK'] = "*";
		//	}
    			$temp_array['FAVORITE'] = "<a href=\"index.php?return_action=".$_REQUEST['action']."&return_module=Feeds&action=DeleteFavorite&module=Feeds&record=".$temp_array['ID']."$view\" class=\"listViewTdToolsS1\">".get_image($image_path."minus_inline",'border="0" align="absmiddle" alt="'.$mod_strings['LBL_DELETE_FAV_BUTTON_LABEL'].'"')."</a>&nbsp;<a href=\"index.php?return_action=".$_REQUEST['action']."&return_module=Feeds&action=DeleteFavorite&module=Feeds&record=".$temp_array['ID']."$view\" class=\"listViewTdToolsS1\">".$mod_strings['LBL_DELETE_FAV_BUTTON_LABEL']."</a>";
		}*/
    	return $temp_array;

	}
	
	function load_feed($feed_url = null, $defer_callback=null, $force_refresh=false) {
		if(! isset($feed_url))
			$feed_url = $this->url;
		if(! $feed_url)
			return false;
		require_once('include/IAH_FeedParser.php');
		$this->feed_obj = new IAH_FeedParser();
		$this->feed_obj->set_feed_url($feed_url);
		if($force_refresh)
			$this->feed_obj->cache = false;
		if($defer_callback)
			return @$this->feed_obj->deferInit($defer_callback);
		else
			return @$this->feed_obj->init();
	}
	
	function feed_loaded() {
		return $this->feed_obj && $this->feed_obj->loaded;
	}
	
	function get_feed_url() {
		return $this->url;
	}
	
	function get_feed_title() {
		$ret = $this->feed_obj->get_title();
		if(! $ret) $ret = '';
		return $ret;
	}
	
	function get_feed_copyright() {
		$ret = $this->feed_obj->get_copyright();
		if(! $ret) $ret = '';
		return $ret;
	}
	
	function get_feed_link() {
		$ret = $this->feed_obj->get_link();
		if(! $ret) $ret = '';
		return $ret;
	}
	
	function get_feed_favicon($html=true) {
		$favicon = $this->feed_obj->get_favicon();
		if($favicon && $html) {
			$favicon = '<img src="'.htmlspecialchars($favicon, ENT_QUOTES, 'UTF-8').'" width="16" height="16" border="0" alt="" style="vertical-align: middle">';
		}
		return $favicon;
	}

	function get_feed_items($limit=0) {
		global $timedate;
		$ret = array();
		$items = $this->feed_obj->get_items(0, $limit);
		foreach($items as $item) {
			$link = $item->get_permalink();
			$title = $item->get_title();
			$author = $item->get_author();
			if(! $author)
				$author = $item->get_contributor();
			if($author)
				$author = array('name' => $author->get_name(), 'email' => $author->get_email(), 'link' => $author->get_link());
			else
				$author = '';
			$content = $item->get_content();
			$content = preg_replace('~<img src="http://feeds.feedburner.com([^>]+)>~', '', $content);
			$description = $item->get_description();
			$date = $item->get_date($timedate->get_date_time_format());
			$ret[] = compact('link', 'title', 'author', 'content', 'description', 'date');
		}
		return $ret;
	}
	
	function get_feed_description() {
		$ret = $this->feed_obj->get_description();
		if(! $ret) $ret = '';
		return $ret;
	}
	
	function get_feed_image() {
		$ret = array();
		$src = $this->feed_obj->get_image_url();
		if($src) {
			$link = $this->feed_obj->get_image_link();
			$width = $this->feed_obj->get_image_width();
			$height = $this->feed_obj->get_image_height();
			$title = $this->feed_obj->get_image_title();
			$html = '<img title="'.htmlspecialchars($title, ENT_QUOTES, 'UTF-8')."\" height=\"$height\" width=\"$width\" src=\"$src\" border=\"0\"/>";
			$link_html = $html;
			if($link)
				$link_html = '<a href="'.htmlspecialchars($link, ENT_QUOTES, 'UTF-8').'" target="_blank" rel="nofollow">' . $link_html . '</a>';
			return compact('src', 'link', 'width', 'height', 'title', 'html', 'link_html');
		}
		return '';
	}
	

	function display_feed()
	{
		global $mod_strings;
		$rssurl = $this->url;

		if (! $this->load_feed()) {
			print $mod_strings['LBL_FEED_NOT_AVAILABLE']."<BR><BR>";
			return;
		}

		$channel_link = htmlspecialchars($this->get_feed_link(), ENT_QUOTES, 'UTF-8');
		$channel_title = $this->get_feed_title();
		$channel_desc = $this->get_feed_description();

		if( ($img = $this->get_feed_image()) )
			$img_html = '<td width="1%" class="rssimg" bgcolor="black">' . $img['link_html'] . '</td>';
		else
			$img_html = '';

		$url = $this->url;

		$content = <<<EOQ

<style type="text/css">
.modtitle { font-family:arial,sans-serif; font-weight:bold; font-size:12pt; color:#000000 }
</style>

<table class="mod" border=0 cellpadding=0 cellspacing=0 width="100%">
<tr>
 <td bgcolor="#ccc">
  <table border=0 cellpadding=2 cellspacing=0 width=100%>
  <tr>
   <td class="modtitle">&nbsp;<a href="$channel_link" target="_blank">$channel_title</a></td>
	$img_html
  </tr>
  </table>
 </td>
</tr>
<tr>
<td>
 <table border=0 cellpadding=0 cellspacing=0 width=100%>

 <tr>
 <td>


<style type="text/css">
.itemtitle { font-family:arial,sans-serif; font-weight:bold; font-size:10pt; color:#000000 }
.itemdate { font-family:arial,sans-serif; font-weight:normal; font-size:8pt; color:#999999 }
.itemdesc { font-family:times,serif; font-weight:normal; font-size:10pt; color:#000000 }
</style>
        <table cellpadding=4 cellspacing=0 width="100%">
EOQ;

		foreach($this->get_feed_items() as $item) {
			//echo item info
			$content .= "<tr><td>\n";
			$content .= "<table cellpadding=0 cellspacing=2><tr>\n";
			$content .= "<td class=\"itemtitle\"><a target=\"_blank\" href=\"".$item['link']."\">".$item['title']."</a></td>\n";
			$content .= "</tr>\n";
			$content .= "<tr><td class=\"itemdate\">".$item['date']."</td></tr>\n";
			$content .= "<tr><td class=\"itemdesc\">".$item['description']."</td></tr>\n";
			$content .= "</table>\n";
			$content .= "</td></tr>\n";

		}
		$content .= '<tr><td bgcolor="#ccc" align=center>' . $this->get_feed_url() . '<br>' . $this->get_feed_copyright() . '</td></tr>';
		$content .= "</table></td></tr></table>\n";
		$content .= "</td></tr></table>\n";

		return $content;
	}
}


?>
