<?php
/**
 * Forum Affiliates Manager
 * Easily manage your forum's affiliates.
 *
 * Version: 1.1
 *
 * Author: Spencer
 */

if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

$plugins->add_hook("admin_config_menu", "affiliates_admin_nav");
$plugins->add_hook("admin_config_permissions", "affiliates_admin_permissions");
$plugins->add_hook("admin_config_action_handler", "affiliates_action_handler");
$plugins->add_hook("admin_load", "affiliates_admin");
$plugins->add_hook("global_start", "affiliates_run");

function affiliates_info()
{
	return array(
		"name"			=> "Forum Affiliates Manager",
		"description"	=> "Easily manage your forum's affiliates.",
		"website"		=> "http://community.mybb.com/user-23387.html",
		"author"		=> "Spencer",
		"authorsite"	=> "http://community.mybb.com/user-23387.html",
		"version"		=> "1.1",
		"guid" 			=> "268b7d5d5bc2892de0f3aefcc82deb99",
		"compatibility" => "16*"
	);
}

function affiliates_install()
{
	global $db;
	
	if(!$db->table_exists("affiliates"))
	{
		$db->write_query("
			CREATE TABLE ".TABLE_PREFIX."affiliates (
			  `id` int(11) NOT NULL AUTO_INCREMENT,
			  `active` int(11) NOT NULL,
			  `name` varchar(225) NOT NULL,
			  `link` varchar(225) NOT NULL,
			  `clicks` int(11) NOT NULL,
			  `image` varchar(225) NOT NULL,
			  PRIMARY KEY (`id`)
			) ENGINE=MyISAM ;
		");
	}
	
	$template1 = array(
		"title"		=> "affiliates",
		"template"	=> $db->escape_string("
<br/><table border=\"0\" cellspacing=\"1\" cellpadding=\"4\" class=\"tborder\"> 
<thead> 
<tr> 
<td class=\"thead\" colspan=\"5\"> 
<div><strong>{\$lang->affiliates}</strong></div> 
</td> 
</tr> 
</thead> 
<tbody> 
<tr class=\"trow1\"> 
<td>
{\$list_affiliates}
</td>
</tr>
</tbody> 
</table>"),
		"sid"		=> "-1",
		"tid" => "NULL",
	);

	$db->insert_query("templates", $template1);
	
	$template2 = array(
		"title"		=> "list_affiliates",
		"template"	=> $db->escape_string("<span style=\"width:{\$maxwidth}px;height:{\$maxheight}px;float:left;margin-right:5px;margin-bottom: 2px;text-align:left;\"><a href=\"{\$mybb->settings['bburl']}/index.php?action=visit&amp;id={\$affiliate['id']}&amp;key={\$mybb->post_code}\"><img src=\"{\$mybb->settings['uploadspath']}/affiliates/{\$affiliate['image']}\" alt=\"\" width=\"auto\" height=\"auto\" title=\"{\$affiliate['name']}\"></a></span>"),
		"sid"		=> "-1",
		"tid" => "NULL",
	);

	$db->insert_query("templates", $template2);
	
	$template3 = array(
		"title"		=> "no_affiliates",
		"template"	=> $db->escape_string("{\$lang->no_affiliates}"),
		"sid"		=> "-1",
		"tid" => "NULL",
	);

	$db->insert_query("templates", $template3);
	
	$affiliates = array(
		"name" => "affiliates",
		"title" => "Forum Affiliates",
		"description" => "Allows you to manage your forum\'s affiliates.",
		"disporder" => "403",
		"isdefault" => "no",
	);
	$group['gid'] = $db->insert_query("settinggroups", $affiliates);
	$gid = $db->insert_id();
	
	$aff[]= array(
		"name"			=> "aff_active",
		"title"			=> "Activate",
		"description"	=> "Do you want to activate the plugin?",
		"optionscode"	=> "yesno",
		"value"			=> '1',
		"disporder"		=> '1',
		"gid"			=> intval($gid),
	);
	
	$aff[]= array(
		"name"			=> "aff_dimensions",
		"title"			=> "Maximum Dimensions",
		"description"	=> "What is the maximum affiliate image dimensions?",
		"optionscode"	=> "text",
		"value"			=> '88x31',
		"disporder"		=> '2',
		"gid"			=> intval($gid),
	);
	
	$aff[]= array(
		"name"			=> "aff_groups_ignore",
		"title"			=> "Disallowed Usergroups",
		"description"	=> "Usergroups not allowed to view the affiliates (separate usergroup id\'s by a comma).",
		"optionscode"	=> "text",
		"value"			=> '',
		"disporder"		=> '3',
		"gid"			=> intval($gid),
	);
	
	$aff[]= array(
		"name"			=> "aff_header",
		"title"			=> "Affiliates Location",
		"description"	=> "Where do you want the affiliates displayed? Your forum\'s header or footer?",
		"optionscode"	=> "radio
1=Header
0=Footer",
		"value"			=> '',
		"disporder"		=> '4',
		"gid"			=> intval($gid),
	);
	
	foreach ($aff as $a)
	{
		$db->insert_query("settings", $a);
	}
		
	rebuild_settings();
	
	include MYBB_ROOT."/inc/adminfunctions_templates.php";
	find_replace_templatesets("footer", '#{\$auto_dst_detection}#', "{\$auto_dst_detection}\n{\$affiliates}");
	find_replace_templatesets("header", '#<navigation>#', "<navigation>\n{\$affiliates_header}");

	change_admin_permission('config', 'affiliates');
}

function affiliates_deactivate()
{
	change_admin_permission('config', 'affiliates', -1);
}

function affiliates_is_installed()
{
	global $db;
	
	return $db->table_exists("affiliates");
}

function affiliates_uninstall()
{
	global $db;
	
	if($db->table_exists("affiliates"))
	{
		$db->drop_table("affiliates");
	}
	
	$db->delete_query('templates', 'title IN (\'affiliates\',\'list_affiliates\',\'no_affiliates\')');
	
	require MYBB_ROOT.'/inc/adminfunctions_templates.php';
	find_replace_templatesets("footer", '#{\$affiliates}(\r?)\n#', "", 0);
	find_replace_templatesets("header", '#{\$affiliates_header}(\r?)\n#', "", 0);
	
	$db->delete_query("settings","name IN ('aff_active')");
	$db->delete_query("settinggroups","name='affiliates'");
	rebuild_settings();
	
	change_admin_permission('config', 'affiliates', -1);
}

function affiliates_run()
{
	global $mybb, $db, $templates, $lang, $affiliates, $affiliates_header;
	
	$lang->load("affiliates");
	
	if($mybb->settings['aff_active'] && (!check_groups($mybb->settings['aff_groups_ignore'])))
	{
		if($mybb->input['action'] == "visit")
		{
			$query = $db->simple_select("affiliates", "*", "id='".intval($mybb->input['id'])."'");
			$affiliate = $db->fetch_array($query);
				
			if(!$affiliate['id'])
			{
				error($lang->invalid_affiliate);
			}
			
			verify_post_check($mybb->input['key']);

			$db->query("UPDATE ".TABLE_PREFIX."affiliates SET clicks = clicks +1 WHERE id='".intval($mybb->input['id'])."'");
			
			header("Location: ".$affiliate['link']."");
		}
		
		$query = $db->simple_select("affiliates", "*", "active='1'", array("order_by" => "id"));

		if($db->num_rows($query) > 0)
		{
			while($affiliate = $db->fetch_array($query))
			{
				list($maxwidth, $maxheight) = explode("x", my_strtolower($mybb->settings['aff_dimensions']));
				eval("\$list_affiliates .= \"".$templates->get("list_affiliates")."\";");
			}
		}
		else
		{
			eval("\$list_affiliates = \"".$templates->get("no_affiliates")."\";");
		}
		
		if($mybb->settings['aff_header'] == 1)
		{
			eval("\$affiliates_header = \"".$templates->get("affiliates")."\";");
		}
		else
		{
			eval("\$affiliates = \"".$templates->get("affiliates")."\";");
		}
	}
}

function affiliates_action_handler(&$action)
{
	$action['affiliates'] = array('active' => 'affiliates', 'file' => '');
}

function affiliates_admin_nav(&$sub_menu)
{
	global $mybb, $lang;

	$lang->load("affiliates", false, true);
		
	end($sub_menu);
	$key = (key($sub_menu))+10;

	if(!$key)
	{
		$key = '100';
	}
	
	$sub_menu[$key] = array('id' => 'affiliates', 'title' => 'Forum Affiliates', 'link' => "index.php?module=config-affiliates");
}

function affiliates_admin_permissions(&$admin_permissions)
{
  	global $db, $mybb, $lang;
		
	$lang->load("affiliates", false, true);
		
	$admin_permissions['affiliates'] = "Can manage forum forum affiliates?";
}

function affiliates_admin()
{
	global $mybb, $db, $page, $lang;
	
	$lang->load("affiliates", false, true);
	
	if($page->active_action != "affiliates")
	{
		return;
	}
	
	$page->add_breadcrumb_item($lang->affiliates);
	
	$sub_tabs['manage'] = array(
		'title' => $lang->manage_tab,
		'link' => "index.php?module=config-affiliates",
		'description' => $lang->manage_desc
	);

	$sub_tabs['add'] = array(
		'title' => $lang->add_tab,
		'link' => "index.php?module=config-affiliates&amp;action=add",
		'description' => $lang->add_desc
	);
	
	if($mybb->input['action'] == "edit")
	{
		$sub_tabs['edit'] = array(
			'title' => $lang->edit_tab,
			'link' => "index.php?module=config-affiliates",
			'description' => $lang->edit_desc
		);		
	}

	if($mybb->input['action'] == "delete")
	{
		$query = $db->simple_select("affiliates", "*", "id='".intval($mybb->input['id'])."'");
		$affiliate = $db->fetch_array($query);

		if(!$affiliate['id'])
		{
			flash_message($lang->error_invalid_partner, 'error');
			admin_redirect("index.php?module=config-affiliates");
		}
		
		if($mybb->input['no'])
		{
			admin_redirect("index.php?module=config-affiliates");
		}
		
		if($mybb->request_method == "post")
		{
			$affimg = $affiliate['image'];
			unlink(MYBB_ROOT.$mybb->settings['uploadspath'].'/affiliates/'.$affimg);
			
			$db->delete_query("affiliates", "id='{$affiliate['id']}'");
			
			flash_message($lang->success_affiliate_deleted, 'success');
			admin_redirect("index.php?module=config-affiliates");
		}
		else
		{
			$page->output_confirm_action("index.php?module=config-affiliates&amp;action=delete&id={$affiliate['id']}", $lang->affiliate_deletion_confirmation);
		}
		
		$page->output_footer();
	}
	
	if($mybb->input['action'] == "edit")
	{
		$page->output_header($lang->affiliates);
		$page->output_nav_tabs($sub_tabs, 'edit');
		
		$query = $db->simple_select("affiliates", "*", "id='".intval($mybb->input['id'])."'");
		$affiliate = $db->fetch_array($query);

		if(!$affiliate['id'])
		{
			flash_message($lang->error_invalid_affiliate, 'error');
			admin_redirect("index.php?module=config-affiliates");
		}
		
		if($mybb->request_method == "post")
		{
			list($width, $height) = @getimagesize($_FILES['image_upload']['tmp_name']);
			list($maxwidth, $maxheight) = explode("x", my_strtolower($mybb->settings['aff_dimensions']));
			switch(strtolower($_FILES['image_upload']['type']))
			{
				case "image/gif":
				case "image/jpeg":
				case "image/x-jpg":
				case "image/x-jpeg":
				case "image/pjpeg":
				case "image/jpg":
				case "image/png":
				case "image/x-png":
					$img_type = 1;
				break;
				default:
					$img_type = 0;
				
			}
			if(!$mybb->input['name'])
			{
				$errors[] = $lang->error_invalid_name;
			}
			if(!preg_match("/^(https?:\/\/+[\w\-]+\.[\w\-]+)/i", $mybb->input['url']))
			{
				$errors[] = $lang->error_invalid_url;
			}
			if($_FILES['image_upload']['name'])
			{
				if(!$_FILES['image_upload'])
				{
					$errors[] = $lang->error_invalid_upload;						
				}
				if($img_type == 0)
				{
					$errors[] = $lang->error_invalid_file_type;
				}	
				if($width > $maxwidth || $height > $maxheight)
				{
					$errors[] = $lang->error_image_too_large = $lang->sprintf($lang->error_image_too_large, $maxwidth, $maxheight);
				}
			}
			if(!$errors)
			{
				if($_FILES['image_upload']['name'])
				{
					$filename = $_FILES['image_upload']['name'];
					$file_basename = substr($filename, 0, strripos($filename, '.'));
					$file_ext = substr($filename, strripos($filename, '.'));
					$filesize = $_FILES['image_upload']['size'];
					$allowed_file_types = array('.png','.jpg','.bmp','.gif');
					
					list($width, $height, $type) = @getimagesize($_FILES['image_upload']['tmp_name']);
					list($maxwidth, $maxheight) = explode("x", my_strtolower($mybb->settings['aff_dimensions']));
					
					// delete old image
					$old_affimg = $affiliate['image'];
					unlink(MYBB_ROOT.$mybb->settings['uploadspath'].'/affiliates/'.$old_affimg);
					
					// upload new image
					$newfilename = random_str(12).$file_ext;
					@move_uploaded_file($_FILES['image_upload']['tmp_name'], MYBB_ROOT.$mybb->settings['uploadspath'].'/affiliates/'.$newfilename);
					
					$update = array(
						"name" => $db->escape_string($mybb->input['name']),
						"link" => $mybb->input['url'],
						"image" => $newfilename,
					);
					$db->update_query("affiliates", $update, "id={$mybb->input['id']}");
			
					flash_message($lang->success_affiliate_edited, 'success');
					admin_redirect("index.php?module=config-affiliates");
				}
				else
				{
					$update = array(
						"name" => $db->escape_string($mybb->input['name']),
						"link" => $mybb->input['url'],
					);
					$db->update_query("affiliates", $update, "id={$affiliate['id']}");
				
					flash_message($lang->success_affiliate_edited, 'success');
					admin_redirect("index.php?module=config-affiliates");
				}
			}
		}
		
		if($errors)
		{
			$page->output_inline_error($errors);
		}
		
		$form = new Form("index.php?module=config-affiliates&amp;action=edit&amp;id={$affiliate['id']}", "post", "", 1);
		
		list($maxwidth, $maxheight) = explode("x", my_strtolower($mybb->settings['aff_dimensions']));
		
		$form_container = new FormContainer($lang->edit_affiliate_info);
		$form_container->output_row($lang->current_image, "", "<span style=\"width:{$maxwidth}px;height:{$maxheight}px;\"><img src=\".".$mybb->settings['uploadspath']."/affiliates/".htmlspecialchars_uni($affiliate['image'])."\" width=\"auto\" height=\"auto\" alt=\"#\"></span>", array('width' => 1));

		$form_container->output_row($lang->name." <em>*</em>", "", $form->generate_text_box('name', $affiliate['name'], $mybb->input['name'], array('id' => 'name')), 'name');
		$form_container->output_row($lang->url." <em>*</em>", $lang->use_http, $form->generate_text_box('url', $affiliate['link'], $mybb->input['url'], array('id' => 'url')), 'url');
		$form_container->output_row($lang->upload_image." <em>*</em>", $lang->sprintf($lang->image_desc, $maxwidth, $maxheight), $form->generate_file_upload_box('image_upload', array('id' => 'image_upload')), 'image_upload');

		$form_container->end();
		$buttons[] = $form->generate_submit_button($lang->button_edit);
		$form->output_submit_wrapper($buttons);

		$form->end();
		$page->output_footer();
	}
	
	if($mybb->input['action'] == "approve")
	{
		global $db;
		
		$array = array(
			"active" => 1
		);
		$db->update_query("affiliates", $array, "id={$mybb->input['id']}");
		flash_message($lang->affiliate_approved, 'success');
		admin_redirect("index.php?module=config-affiliates");
	}
	
	if($mybb->input['action'] == "unapprove")
	{
		global $db;
		
		$array = array(
			"active" => 0
		);
		$db->update_query("affiliates", $array, "id={$mybb->input['id']}");
		flash_message($lang->affiliate_unapproved, 'success');
		admin_redirect("index.php?module=config-affiliates");
	}
	
	if($mybb->input['action'] == "add")
	{
		$page->output_header($lang->affiliates);
		$page->output_nav_tabs($sub_tabs, 'add');

		if($mybb->request_method == "post")
		{
			list($width, $height) = @getimagesize($_FILES['image_upload']['tmp_name']);
			list($maxwidth, $maxheight) = explode("x", my_strtolower($mybb->settings['aff_dimensions']));
			switch(strtolower($_FILES['image_upload']['type']))
			{
				case "image/gif":
				case "image/jpeg":
				case "image/x-jpg":
				case "image/x-jpeg":
				case "image/pjpeg":
				case "image/jpg":
				case "image/png":
				case "image/x-png":
					$img_type = 1;
				break;
				default:
					$img_type = 0;
				
			}
			if(!$_FILES['image_upload'])
			{
				$errors[] = $lang->error_invalid_upload;						
			}
			if(!$mybb->input['name'])
			{
				$errors[] = $lang->error_invalid_name;
			}
			if(!preg_match("/^(https?:\/\/+[\w\-]+\.[\w\-]+)/i", $mybb->input['url']))
			{
				$errors[] = $lang->error_invalid_url;
			}
			if($img_type == 0)
			{
				$errors[] = $lang->error_invalid_file_type;
			}	
			if($width > $maxwidth || $height > $maxheight)
			{
				$errors[] = $lang->error_image_too_large = $lang->sprintf($lang->error_image_too_large, $maxwidth, $maxheight);
			}
			elseif(!$errors)
			{
				$filename = $_FILES['image_upload']['name'];
				$file_ext = substr($filename, strripos($filename, '.'));
				$filesize = $_FILES['image_upload']['size'];
				
				$process_upload = random_str(12).$file_ext;
				@move_uploaded_file($_FILES['image_upload']['tmp_name'], MYBB_ROOT.$mybb->settings['uploadspath'].'/affiliates/'.$process_upload);

				$insert = array(
					"active" => 1,
					"name" => $db->escape_string($mybb->input['name']),
					"link" => $mybb->input['url'],
					"image" => $process_upload
				);
				$db->insert_query("affiliates", $insert);
		
				flash_message($lang->success_affiliate_added, 'success');
				admin_redirect("index.php?module=config-affiliates");				
			}
		}
		
		if($errors)
		{
			$page->output_inline_error($errors);
		}
		
		list($maxwidth, $maxheight) = explode("x", my_strtolower($mybb->settings['aff_dimensions']));
		
		$form = new Form("index.php?module=config-affiliates&amp;action=add", "post", "", 1);
		
		$form_container = new FormContainer($lang->add_affiliate_info);
		$form_container->output_row($lang->name." <em>*</em>", $lang->name_desc, $form->generate_text_box('name', $mybb->input['name'], array('id' => 'name')), 'name');
		$form_container->output_row($lang->url." <em>*</em>", $lang->use_http, $form->generate_text_box('url', $mybb->input['url'], array('id' => 'url')), 'url');
		$form_container->output_row($lang->upload_image." <em>*</em>", $lang->sprintf($lang->image_desc, $maxwidth, $maxheight), $form->generate_file_upload_box('image_upload', array('id' => 'image_upload')), 'image_upload');

		$form_container->end();
		$buttons[] = $form->generate_submit_button($lang->button_add);
		$form->output_submit_wrapper($buttons);

		$form->end();
		
		$page->output_footer();
	}
	
	if(!$mybb->input['action'])
	{
		$page->output_header($lang->affiliates);
		$page->output_nav_tabs($sub_tabs, 'manage');
		
		$form = new Form("index.php?module=tools/pms&amp;action=delete", "post");
		
		$table = new Table;
		$table->construct_header("", array("colspan" => 1, "width" => "1%", "class" => "align_center"));
		$table->construct_header($lang->name, array("colspan" => 1));
		$table->construct_header($lang->preview, array("colspan" => 1, "width" => "13%", "class" => "align_center"));
		$table->construct_header($lang->clicks, array("colspan" => 1, "width" => "5%", "class" => "align_center"));
		$table->construct_header($lang->actions, array("colspan" => 1, "width" => "5%", "class" => "align_center"));
		
		$query = $db->simple_select("affiliates", "*", "", array("order_by" => "id"));
		
		while($affiliate = $db->fetch_array($query))
		{
			if($affiliate['active'] == 1)
			{
				$active = "<img src=\"".$mybb->settings['bburl']."/images/minion.gif\" title=\"Approved\">";
			}
			else
			{
				$active = "<img src=\"".$mybb->settings['bburl']."/images/minioff.gif\" title=\"Unapproved\">";
			}
			
			list($maxwidth, $maxheight) = explode("x", my_strtolower($mybb->settings['aff_dimensions']));
			
			$table->construct_cell($active, array("class" => "align_center"));
			$table->construct_cell("<a href=\"".$affiliate['link']."\" target=\"_blank\">".$affiliate['name']."</a>");
			$table->construct_cell("<span style=\"width:{$maxwidth}px;height:{$maxheight}px;\"><img src=\".".$mybb->settings['uploadspath']."/affiliates/".$affiliate['image']."\" width=\"auto\" height=\"auto\" alt=\"#\"></span>", array("class" => "align_center"));
			$table->construct_cell($affiliate['clicks'], array("class" => "align_center"));
			
			$popup = new PopupMenu("affiliate_{$affiliate['id']}", $lang->options);
			$popup->add_item($lang->edit_affiliate, "index.php?module=config-affiliates&amp;action=edit&amp;id={$affiliate['id']}");
			$popup->add_item($lang->delete_affiliate, "index.php?module=config-affiliates&amp;action=delete&amp;id={$affiliate['id']}&amp;my_post_key={$mybb->post_code}", "return AdminCP.deleteConfirmation(this, '{$lang->affiliate_deletion_confirmation}')");
			if($affiliate['active'] == 1)
			{
				$popup->add_item($lang->unapprove_affiliate, "index.php?module=config-affiliates&amp;action=unapprove&amp;id={$affiliate['id']}");
			}
			else
			{
				$popup->add_item($lang->approve_affiliate, "index.php?module=config-affiliates&amp;action=approve&amp;id={$affiliate['id']}");
			}
			$table->construct_cell($popup->fetch());
			$table->construct_row();
		}
		
		if($table->num_rows() == 0)
		{
			$table->construct_cell($lang->no_affiliates_found, array("colspan" => "6"));
			$table->construct_row();
			$table->output($lang->manage);
		}
		else
		{
			$table->output($lang->manage);
		}
		
		$form->end();
		
		$page->output_footer();
	}
	exit;
}

function check_groups($groups_check)
{
    global $mybb;
    
    if($groups_check == '')
    {
        return false;
    }
    
    $groups = explode(',', $groups_check);
    $add_groups = explode(',', $mybb->user['additionalgroups']);
    
    if(!in_array($mybb->user['usergroup'], $groups))
    {
        if($add_groups)
        {
            if(count(array_intersect($add_groups, $groups)) == 0)
            {
                return false;
            }
            else
            {
                return true;
            }
        }
        else
        {
            return false;
        }
    }
    else
    {
        return true;
    }
} 
?>