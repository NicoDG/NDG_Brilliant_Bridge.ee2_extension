<?php
//error_reporting(1);
if ( ! defined('BASEPATH')) exit('No direct script access allowed');
error_reporting(0);
class Ndg_brilliant_bridge_ext {

	var $settings       = array();
	var $name           = 'NDG Brilliant Bridge';
	var $version        = '1.0';
	var $description    = 'Makes the bridge between Brilliant Retail products and Channel entries for easy setup of product commenting';
	var $settings_exist = 'y';
	var $docs_url       = '';

				
	function Ndg_brilliant_bridge_ext($settings='') {

	
    	$this->EE =& get_instance();
	
		$this->EE->lang->loadfile('ndg_brilliant_bridge');
		
		$this->settings = $settings;
		
		$results = $this->EE->db->query("SELECT module_id FROM ".$this->EE->db->dbprefix('modules')." WHERE module_name = 'Brilliant_retail'");
		
		$this->settings["br_installed"] = false;
	    if ($results->num_rows > 0){$this->settings["br_installed"] = true;}
	    
	    $this->settings["channel_id"] = (!isset($this->settings["channel_id"])) ? "" : $this->settings["channel_id"];
	    
	    
	}	
	
	function create($data){
	
		if (!$this->settings["br_installed"] || $this->settings["channel_id"] == "")
		{
			
			show_error($this->EE->lang->line('no_channel_selected'));
		}
		else
		{
		
			//insert entry in the BR Bridge channel
			$this->EE->api->instantiate('channel_entries');
		
			$entry = array(
			"entry_id" => 0, 
			"channel_id" => $this->settings["channel_id"], 
			"title" => $data["title"], 
			"url_title" => url_title($data["title"], 'dash', TRUE), 
			"entry_date" => date("Y-m-d H:i A"),
			"new_channel" => $this->settings["channel_id"], 
			"allow_comments" => "y",
			"status" => "open", 
			"cp_call" => 1
			); 
			
			$success	= $this->EE->api_channel_entries->submit_new_entry($this->settings["channel_id"], $entry);
	
			//store entry id in BR Bridge lookup table
			$entry_id	= $this->EE->api_channel_entries->entry_id;
			
			$bridge_data = array(
				'entry_id'			=> $entry_id,
				'product_id'		=> $data["product_id"],
				'site_id'			=> $this->EE->config->item('site_id')
			);
			
			$where = array(
				'product_id' 		=> $data["product_id"]
			);
			 
			$this->EE->db->query($this->EE->db->insert_string($this->EE->db->dbprefix('ndg_brilliant_bridge_lookup'), $bridge_data));		
		}
		
		return $data;
	}
	

	function update($data){

		if (!$this->settings["br_installed"] || $this->settings["channel_id"] == "")
		{
			
			show_error($this->EE->lang->line('no_channel_selected'));
		}
		else
		{
		
			//look up entry id based on $data["product_id"] in BR Bridge lookup table
			$this->EE->db->select('entry_id');
			$this->EE->db->where('product_id', $data["product_id"]); 
			$this->EE->db->where('site_id', $this->EE->config->item('site_id')); 
			$this->EE->db->from($this->EE->db->dbprefix('ndg_brilliant_bridge_lookup'));
		
			$query = $this->EE->db->get();
	
			if ($query->num_rows() > 0)
			{
			
				$entry_id = $query->row()->entry_id;
				
				$this->EE->api->instantiate('channel_entries');
			
				$entry = array(
				"entry_id" => $entry_id, 
				"channel_id" => $this->settings["channel_id"], 
				"title" => $data["title"], 
				"url_title" => url_title($data["title"], 'dash', TRUE), 
				"entry_date" => date("Y-m-d H:i A"),
				"new_channel" => $this->settings["channel_id"],
				"allow_comments" => "y", 
				"status" => "open", 
				"cp_call" => 1
				); 	
				
				if($this->EE->api_channel_entries->entry_exists($entry_id)){
					
					$success = $this->EE->api_channel_entries->update_entry($entry_id, $entry);
				
				}else{
				
					$success = $this->EE->api_channel_entries->submit_new_entry($this->settings["channel_id"], $entry);
					
				}
				
				//update entry in the BR Bridge channel
			
				$bridge_data = array(
					'entry_id'			=> $entry_id,
					'product_id'		=> $data["product_id"],
					'site_id'			=> $this->EE->config->item('site_id')
				);
				
				$where = array(
					'product_id' 		=> $data["product_id"]
				);
				 
				$this->EE->db->query($this->EE->db->update_string($this->EE->db->dbprefix('ndg_brilliant_bridge_lookup'), $bridge_data, $where));		
				
				
			
			}else{
			
				//CREATE NEW ENTRY
			
				$this->create($bridge_data);
			
			}
			
		}	
		
		return $data;
	}
	
	function delete($data){
	
		//look up entry id based on $data["product_id"] in BR Bridge lookup table
		$this->EE->db->select('entry_id');
		$this->EE->db->where('product_id', $data["product_id"]); 
		$this->EE->db->where('site_id', $this->EE->config->item('site_id')); 
		$this->EE->db->from($this->EE->db->dbprefix('ndg_brilliant_bridge_lookup'));
	
		$query = $this->EE->db->get();

		if ($query->num_rows() > 0)
		{
		
			$entry_id = $query->row()->entry_id;
			
			// Delete primary data
			$this->EE->db->where_in('entry_id', $entry_id);
			$this->EE->db->delete(array('channel_titles', 'channel_data', 'category_posts'));
			
		}
		
		$this->EE->db->where('product_id', $data["product_id"]);
		$this->EE->db->delete($this->EE->db->dbprefix('ndg_brilliant_bridge_lookup')); 
		
		
		return $data;
			
	}
		
	/** -------------------------------------
	/** Activate
	/** -------------------------------------*/

	function activate_extension() {

		$this->EE->load->dbforge();
		
		$data = array(
			'class'        => "Ndg_brilliant_bridge_ext",
			'method'       => "create",
			'hook'         => 'br_product_create_after',
			'settings'     => "",
			'priority'     => 1,
			'version'      => $this->version,
			'enabled'      => "y"
		);

		$this->EE->db->insert('exp_extensions', $data);
		
		$data = array(
			'class'        => "Ndg_brilliant_bridge_ext",
			'method'       => "update",
			'hook'         => 'br_product_update_after',
			'settings'     => "",
			'priority'     => 2,
			'version'      => $this->version,
			'enabled'      => "y"
		);

		$this->EE->db->insert('exp_extensions', $data);
		
		$data = array(
			'class'        => "Ndg_brilliant_bridge_ext",
			'method'       => "delete",
			'hook'         => 'br_product_delete',
			'settings'     => "",
			'priority'     => 3,
			'version'      => $this->version,
			'enabled'      => "y"
		);

		$this->EE->db->insert('exp_extensions', $data);
		
		$fields = array(
			'entry_id'		=>	array('type' => 'int', 'constraint' => '10', 'unsigned' => TRUE, 'null' => FALSE),
			'product_id'	=>	array('type' => 'int', 'constraint' => '10', 'unsigned' => TRUE, 'null' => FALSE),
			'site_id'		=>  array('type' 		 => 'int','constraint'	=> '4', 'unsigned'		 => TRUE)
			);
		
		$this->EE->dbforge->add_field($fields);
		
		
		$this->EE->dbforge->create_table('ndg_brilliant_bridge_lookup', TRUE);
		
	}


	/** -------------------------------------
	/** Update Extension
	/** -------------------------------------*/
	
	function update_extension($current='') {

		if ($current == '' OR $current == $this->version) {
			return FALSE;
		}
		$this->EE->db->where('class', 'Ndg_brilliant_bridge_ext');
		$this->EE->db->update('extensions', array('version' => $this->version));
	}


	/** -------------------------------------
	/** Disable
	/** -------------------------------------*/
	
	function disable_extension() {

	    $this->EE->db->where('class', 'Ndg_brilliant_bridge_ext');
	    $this->EE->db->delete('exp_extensions');
	
	}

	/** -------------------------------------
	/** settings
	/** -------------------------------------*/	
	function settings()
	{
		$settings = array();

		$settings['channel_id'] = "";

		return $settings;
	}

	/** -------------------------------------
	/** Settings Form
	/** -------------------------------------*/
	function settings_form($current)
	{
	
		$this->EE->load->helper('form');
		$this->EE->load->library('table');	
		$this->EE->load->library("cp");
		$this->EE->load->model('channel_model');
		
		$channel_id	= isset($current['channel_id']) ? $current['channel_id'] : "";

	    $allowed_channels = $this->EE->functions->fetch_assigned_channels();
	    
	    $fields = array('channel_title', 'channel_id', 'cat_group');
		$where = array();
		
		// If the user is restricted to specific channels, add that to the query
		
		if ($this->EE->session->userdata['group_id'] != 1)
		{
			$where[] = array('channel_id' => $allowed_channels);
		}

		$query = $this->EE->channel_model->get_channels($this->EE->config->item('site_id'), $fields, $where);
		
		$channels = array("" => "Select Channel");
		
		if ($query->num_rows() > 0){
			foreach($query->result_array() as $row)
			{
				$channels[$row['channel_id']] = $row['channel_title'];
			}
		}
	    
	    $vars = array();
	    
		$vars['settings'] = array(
			'channel_id'				=> form_dropdown('channel_id', $channels, $channel_id)
		);

		return $this->EE->load->view('extension_index', $vars, TRUE);	
		
	}

	
	/** -------------------------------------
	/** Save settings
	/** -------------------------------------*/
	
	function save_settings()
	{
		
		if (empty($_POST))
		{
			show_error($this->EE->lang->line('unauthorized_access'));
		}
	
		if ($_POST["channel_id"] == "")
		{
			show_error($this->EE->lang->line('no_channel_submit'));
		}
		
		unset($_POST['submit']);
		
		$this->EE->db->where('class', __CLASS__);
		$this->EE->db->update('extensions', array('settings' => serialize($_POST)));
		
		
		$this->sync_entries_and_products($_POST["channel_id"]);
		$this->EE->session->set_flashdata('message_success', $this->EE->lang->line('preferences_updated'));
		
	}
	
	
	function sync_entries_and_products($channel_id){
		
		error_reporting(0);
		
		$this->settings["channel_id"] = $channel_id;
		
		$this->EE->db->select('product_id, site_id, title');
		$this->EE->db->where('site_id', $this->EE->config->item('site_id')); 
		$this->EE->db->from($this->EE->db->dbprefix('br_product'));
	
		$query = $this->EE->db->get();

		if ($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row){
				$row["channel_id"] = $channel_id;
				$this->update($row);
			}
		}

	}
	
	
}