<?php

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$plugin_info = array(
  'pi_name' => 'NDG Brilliant Bridge',
  'pi_version' => '1.0',
  'pi_author' => 'Nico De Gols',
  'pi_author_url' => 'http://www.hiredguns.be/',
  'pi_description' => 'Gets channel entry id\'s based on Brilliant Retail product id\'s',
  'pi_usage' => Ndg_bridge::usage()
  );


class Ndg_bridge
{

var $return_data = "";


  function __construct()
  {
    $this->EE =& get_instance();

	$product_id = $this->EE->TMPL->fetch_param('product_id');

  }
  
  function entry_id(){
  
  	$product_id = $this->EE->TMPL->fetch_param('product_id');
  
  
  	$this->EE->db->select('entry_id');
	$this->EE->db->where('product_id', $product_id); 
	$this->EE->db->where('site_id', $this->EE->config->item('site_id')); 
	$this->EE->db->from($this->EE->db->dbprefix('ndg_brilliant_bridge_lookup'));
		
	$query = $this->EE->db->get();

	if ($query->num_rows() > 0)
	{
		$entry_id = $query->row()->entry_id;	
	}else{
		$entry_id = 0;
	}
	
	$this->return_data  = $entry_id;
	
	return $this->return_data;
			
  }

  
  function product_id(){
  
  	$entry_id = $this->EE->TMPL->fetch_param('entry_id');
  
  
  	$this->EE->db->select('product_id');
	$this->EE->db->where('entry_id', $entry_id); 
	$this->EE->db->where('site_id', $this->EE->config->item('site_id')); 
	$this->EE->db->from($this->EE->db->dbprefix('ndg_brilliant_bridge_lookup'));
		
	$query = $this->EE->db->get();

	if ($query->num_rows() > 0)
	{
		$product_id = $query->row()->product_id;	
	}else{
		$product_id = 0;
	}
	
	$this->return_data  = $product_id;
	
	return $this->return_data;
			
  }
  
	// --------------------------------------------------------------------

	/**
	 * Usage
	 *
	 * This function describes how the plugin is used.
	 *
	 * @access	public
	 * @return	string
	 */
	
  //  Make sure and use output buffering

  function usage()
  {
  ob_start(); 
  ?>
Use the following tag inside the {exp:brilliant_retail:product} tag to get the corresponding channel entry_id based on the Brilliant Retail product id

{exp:ndg_bridge:entry_id product_id='{product_id}'}


List product comments:
_______________________________________________________________________________________________________________

{exp:comment:entries sort="asc" dynamic="no" entry_id="{exp:ndg_bridge:entry_id product_id='{product_id}'}" parse="inward"}
	{comment}
	<p>By {name} on {comment_date format="%Y %m %d"}</p>
{/exp:comment:entries}


Product comment form:
_______________________________________________________________________________________________________________

{exp:comment:form dynamic="no" entry_id="{exp:ndg_bridge:entry_id product_id='{product_id}'}" parse="inward"}
	{if logged_out}
	<p>Name: <input type="text" name="name" value="{name}" size="50" /></p>
	<p>Email: <input type="text" name="email" value="{email}" size="50" /></p>
	<p>Location: <input type="text" name="location" value="{location}" size="50" /></p>
	<p>URL: <input type="text" name="url" value="{url}" size="50" /></p>
	{/if}
	<p><textarea name="comment" cols="70" rows="10">{comment}</textarea></p>
	<p><input type="checkbox" name="save_info" value="yes" {save_info} /> Remember my personal information</p>
	<p><input type="checkbox" name="notify_me" value="yes" {notify_me} /> Notify me of follow-up comments?</p>
	<input type="submit" name="submit" value="Submit" />
	<input type="submit" name="preview" value="Preview" />
{/exp:comment:form}


Get other data assigned to the channel entries:
_______________________________________________________________________________________________________________

{exp:channel:entries channel="br_bridge"  show_future_entries="yes" dynamic="no" entry_id="{exp:ndg_bridge:entry_id product_id='{product_id}'}" parse="inward"}
	Data from the product channel entry: {title}
{/exp:channel:entries}

  <?php
  $buffer = ob_get_contents();
	
  ob_end_clean(); 

  return $buffer;
  }
  // END

}
/* End of file pi.ndg_brilliant_bridge.php */ 
/* Location: ./system/expressionengine/third_party/ndg_brilliant_bridge/pi.ndg_brilliant_bridge.php */