<div class="rightNav">
	<div style="float: left; width: 100%;">
		<span class="button"><? echo '<a href="'.BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=libraree" class="submit" >LibrarEE file overview</a>'; ?></span>
	</div>
</div>
<b>Instructions:</b><br/>
Select a channel you wish to use to "mirror" Brilliant Retail products, this is preferably a channel specific for these products.<br/>  
You can add custom fields to this channel and the data will be available on the product page.<br/>
This will allow instant activation of product commenting.<br/>	<br/>  
<?=form_open('C=addons_extensions'.AMP.'M=save_extension_settings'.AMP.'file=ndg_brilliant_bridge');?>

<?php 
$this->table->set_template($cp_pad_table_template);
$this->table->set_heading(
    array('data' => lang('preference'), 'style' => 'width:50%;'),
    lang('setting')
);

foreach ($settings as $key => $val)
{
	$this->table->add_row(lang($key, $key), $val);
}

echo $this->table->generate();

?>

<p><?=form_submit('submit', lang('submit'), 'class="submit"')?></p>
<?php $this->table->clear()?>
<?=form_close()?>
