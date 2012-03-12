<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');
# get correct id for plugin
$thisfile = basename(__FILE__, ".php");

# register plugin
register_plugin(
	$thisfile, 
	'FAQ Manager', 	
	'1.1', 		
	'Mike Henken',
	'http://michaelhenken.com/', 
	'Manage frequently asked questions',
	'pages',
	'FAQ_Admin'  
);

add_action('pages-sidebar','createSideMenu',array($thisfile,'F.A.Q. Manager'));
define('FAQLimit', 4);
define('FAQFile', GSDATAOTHERPATH  . 'faq.xml');
add_filter('content','faq_replace');

global $EDLANG, $EDOPTIONS, $toolbar, $EDTOOL;
if (defined('GSEDITORLANG')) { $EDLANG = GSEDITORLANG; } else {	$EDLANG = 'en'; }
if (defined('GSEDITORTOOL')) { $EDTOOL = GSEDITORTOOL; } else {	$EDTOOL = 'basic'; }
if (defined('GSEDITOROPTIONS') && trim(GSEDITOROPTIONS)!="") { $EDOPTIONS = ", ".GSEDITOROPTIONS; } else {	$EDOPTIONS = ''; }
if ($EDTOOL == 'advanced') {
$toolbar = "
	    ['Bold', 'Italic', 'Underline', 'NumberedList', 'BulletedList', 'JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock', 'Table', 'TextColor', 'BGColor', 'Link', 'Unlink', 'Image', 'RemoveFormat', 'Source'],
    '/',
    ['Styles','Format','Font','FontSize']
";
} elseif ($EDTOOL == 'basic') {
$toolbar = "['Bold', 'Italic', 'Underline', 'NumberedList', 'BulletedList', 'JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock', 'Link', 'Unlink', 'Image', 'RemoveFormat', 'Source']";
} else {
$toolbar = GSEDITORTOOL;
}

class FAQ
{
	public function __construct()
	{
		if(!file_exists(FAQFile))
		{
			$xml = new SimpleXMLExtended('<?xml version="1.0" encoding="UTF-8"?><channel></channel>');
			if(XMLsave($xml, FAQFile))
			{
					echo '<div class="updated">File Succesfully Written</div>';
			}
		}
	}
	
	public function getFAQData($attribute, $file_data)
	{
		$data_file = getXML(FAQFile);
		foreach($data_file->category as $category)
		{
			foreach($category->content as $faq)
			{
				$c_atts= $faq->attributes();
				if(isset($c_atts['title']) && $c_atts['title'] == $attribute)
				{
					if($file_data == 'title')
					{
						return $c_atts['title'];
					}
					elseif($file_data == 'category')
					{
						return $category;
					}
					else
					{
						return $faq;
					}
				}
			}
		}
	}
	
	public function processFAQData($edit=null,$delete_category=null,$edit_category=null,$delete_faq=null)
	{
		$faq_file = getXML(FAQFile);
		$xml = new SimpleXMLExtended('<?xml version="1.0" encoding="UTF-8"?><channel></channel>');
		foreach($faq_file->category as $category)
		{	
			$c_atts= $category->attributes();
			if($delete_category != null && $delete_category == $c_atts['name'])
			{
				//Do nothign. Do not add it to new xml file
			}
			elseif($edit_category != null && $edit_category == $c_atts['name'])
			{
				$c_child = $xml->addChild('category');
				$c_child->addAttribute('name', $_POST['title']);
			}
			else
			{
				$c_child = $xml->addChild('category');
				$c_child->addAttribute('name', $c_atts['name']);
			}
			
			foreach($category->content as $content)
			{
				$atts= $content->attributes();
				if($edit != null && $c_atts['name'] == $_POST['category'] && $edit == $atts['title'])
				{
					$child = $c_child->addChild('content');
					$child->addAttribute('title', $_POST['title']);
					$child->addCData($_POST['contents']);
				}
				else
				{
					$child = $c_child->addChild('content');
					$child->addAttribute('title', $atts['title']);
					$child->addCData($content);
				}
			}
			
			if(isset($_POST['add_new_faq']) && $_POST['category'] ==  $c_atts['name'])
			{
				$child = $c_child->addChild('content');
				$child->addAttribute('title', $_POST['title']);
				$child->addCData($_POST['contents']);
			}
		}
		
		if(isset($_POST['new_category']))
		{
			$c_child = $xml->addChild('category');
			$c_child->addAttribute('name', $_POST['title']);
		}
		
		if(XMLsave($xml, FAQFile))
		{
			if($edit != null && $delete_category == null && $delete_faq == null)
			{
				echo '<div class="updated">File Successfully Edited</div>';
			}
			elseif($edit != null && $delete_faq != null)
			{
				echo '<div class="updated">Question Successfully Deleted</div>';
			}
			elseif($delete_category != null)
			{
				echo '<div class="updated">Category Successfully Deleted</div>';
			}
			else
			{
				echo '<div class="updated">Successfully Created</div>';
			}
		}
	}
	
	public function deleteFAQ()
	{
	
	}
	
	public function adminHeader()
	{
	?>
		<div style="width:100%;margin:0 -15px -15px -10px;padding:0px;">
			<h3 class="floated">FAQ Manager</h3>
			<div class="edit-nav clearfix" style="">
				<a href="load.php?id=faq&faq_help" <?php if (isset($_GET['faq_help'])) { echo 'class="current"'; } ?>>Help</a>
				<a href="load.php?id=faq&faq_categories" <?php if (isset($_GET['faq_categories'])) { echo 'class="current"'; } ?>>Categories</a>
				<a href="load.php?id=faq">View All</a>
			</div> 
		</div>
		</div>
		<div class="main" style="margin-top:-10px;">
	<?php
	}
	
	public function showViewAllFAQ()
	{
		$faq_file = getXML(FAQFile);
	?>
		<h3 class="floated">All FAQ</h3>
			<div class="edit-nav clearfix" style="">
				<a href="load.php?id=faq&add_faq" class="ra_help_button">Add New Question</a>
		</div>
		
	<?php

		$xml = new SimpleXMLExtended('<?xml version="1.0" encoding="UTF-8"?><channel></channel>');

		foreach($faq_file->category as $category)
		{
			$content_count = '0';
			$c_atts=$category->attributes();
			echo '<h2 style="font-size:16px;">'.$c_atts['name'].'</h2><table class="highlight">';
			foreach($category->content as $the_content)
			{	
				$content_count++;
				$atts = $the_content->attributes();
				?>
				<tr>
					<td>
						<a href="load.php?id=faq&edit_faq=<?php echo $atts['title']; ?>" title="Edit Content: <?php echo $atts['title']; ?>">
						<?php echo $atts['title']; ?>
						</a>
					</td>
					<td class="delete">
						<a href="load.php?id=faq&delete=<?php echo $atts['title']; ?>&category_of_deleted=<?php echo $c_atts['name']; ?>" class="delconfirm" title="Delete Content: <?php echo $atts['title']; ?>?">X</a>
					</td>
				</tr>
<?php
			}
			echo '</table>';
			echo '<p><b>' . $content_count . '</b> questions</p>';
		}
	}
	
	public function showViewAllCategories()
	{
	
	}
	
	public function showEditFAQ($edit_faq=null)
	{
		if($edit_faq != null)
		{	
			$faq_title = $this->getFAQData($edit_faq, 'title');
			$faq_edit_add = 'Edit '.$edit_faq;
			$faq_category = $this->getFAQData($edit_faq, 'category');
			$faq_content = $this->getFAQData($edit_faq, 'content');
			$add_new_hidden_field = '
			<input type="hidden" name="edit_faq" value="'.$edit_faq.'" />
			<input type="hidden" name="old-title" value="'.$faq_title.'" />
			';
		}
		else
		{
			$faq_edit_add = 'Add New Question';
			$faq_title = 'Title..';
			$faq_category = '';
			$faq_content = '';
			$add_new_hidden_field = '<input type="hidden" name="add_new_faq" />';
		}
		global $EDLANG, $EDOPTIONS, $toolbar, $EDTOOL, $SITEURL;
		?>
		<h3><?php echo $faq_edit_add; ?></h3>
		<form action="" method="post" accept-charset="utf-8">
			<?php echo $add_new_hidden_field; ?>
			<input type="text" name="title" class="text" style="width:635px;" value="<?php echo $faq_title; ?>" onFocus="if(this.value == 'Title..') {this.value = '';}" onBlur="if (this.value == '') {this.value = 'Title..';}" />
			<select name="category" class="text" style="width:647px;margin:5px 0px 5px 0px">
				<?php
					if($edit_faq != null)
					{
						$selected_choice = '<option value="'.$faq_category.'">'.$faq_category.'</option>';
					}
					else 
					{
						$selected_choice = '';
						echo '<option value="">Choose Category...</option>';
					}
					$content_file = getXML(FAQFile);
					foreach($content_file->category as $edit_cate)
					{	
						$atts = $edit_cate->attributes();
						if($selected_choice == $atts['name'])
						{
							echo '<option value="'.$selected_choice.'">'.$selected_choice.'</option>';
						}
						else
						{
							echo '<option value="'.$atts['name'].'">'.$atts['name'].'</option>';
						}
					}
				?>
			</select>

			<textarea id="post-content" name="contents"><?php echo $faq_content; ?></textarea>
			<script type="text/javascript" src="template/js/ckeditor/ckeditor.js"></script>
			<script type="text/javascript">
			  // missing border around text area, too much padding on left side, ...
			  $(function() {
				CKEDITOR.replace( 'contents', {
						skin : 'getsimple',
						forcePasteAsPlainText : false,
						language : '<?php echo $EDLANG; ?>',
						defaultLanguage : '<?php echo $EDLANG; ?>',
						entities : true,
						uiColor : '#FFFFFF',
							height: '200px',
							baseHref : '<?php echo $SITEURL; ?>',
						toolbar : [ <?php echo $toolbar; ?> ]
							<?php echo $EDOPTIONS; ?>
				})
			  });
			</script><br/>
			<input type="submit" class="submit" value="Add Content" style="float:right;"/>
		</form>
		<div style="clear:both">&nbsp;</div>
		<?php
	
	}
	
	public function showEditCategory()
	{
		$faq_data = getXML(FAQFile);
	?>
		<h3 class="floated">Manage Categories</h3>
		<div class="edit-nav clearfix" style="">
				<a href="#" class="ra_help_button">Add New Category</a>
		</div>
		<div class="ra_help" style="display:none;padding:10px;background-color:#f6f6f6;margin:10px;">
			<h3>Add Category</h3>  
			<form action="" method="post" accept-charset="utf-8">
				<input type="hidden" name="new_category" />
				<p>
					<input type="text" name="title" class="text" style="width:600px;" value="Category Title.." onFocus="if(this.value == 'Category Title..') {this.value = '';}" onBlur="if (this.value == '') {this.value = 'Category Title..';}" />
				</p>
				<input type="submit" class="submit" value="Add Category" style="float:right;"/>
			</form>		
			<div style="clear:both">&nbsp;</div>
			<script type="text/javascript">
				$(document).ready(function() {
					$('.ra_help_button').click(function() {
						$('.ra_help').show();
						$('.ra_help_button').hide();
					})
				})
			</script>
		</div>
	<?php
		echo '<table class="highlight">';
		$content_count = '0';
			$showings_count = 0;
		foreach($faq_data->category as $category)
		{
			$content_count++;
			$showings_count++;
			$c_atts= $category->attributes();
			?>
			<form action="" method="POST">
				<input type="hidden" name="edit_category_name" value="<?php echo $c_atts['name']; ?>"/>
			<tr>
				<td>
							<input class="title<?php echo $showings_count; ?>" style="display:none;width:270px;float:left;" name="title" value="<?php echo $c_atts['name']; ?>">
							<input class="submit<?php echo $showings_count; ?>" type="submit" value="Submit?" style="display:none;float:right;margin-right:30px;padding:2px;font-size:10px;" />
							<span class="title<?php echo $showings_count; ?>" ONCLICK="showinput('title<?php echo $showings_count; ?>','submit<?php echo $showings_count; ?>')" style="color:inherit;font-size:inherit;line-height:inherit;"><?php echo $c_atts['name']; ?></span>
				</td>
				<td class="delete">
					<a href="load.php?id=faq&faq_categories&delete_category=<?php echo $c_atts['name']; ?>" class="delconfirm" title="Delete Category: <?php echo $c_atts['name']; ?>?? This Will Delete ALL* content In The Category As Well!">
					X
					</a>
				</td>
			</tr>
			</form>
	<?php
	}
	?>
			<script type="text/javascript">
				function showinput(a_type,a_submit){
						$('input.'+[a_type]).show();
						$('input.'+[a_submit]).show();
						$('span.'+[a_type]).hide();
				}
			</script>
			</table>
		<?php
		echo '<p><b>' . $content_count . '</b> content</p>';	
	}
	
	public function showHelp()
	{
		?>
		<h3>FAQ Plugin Instructions</h3>
		<strong>The below function will display all FAQ categories and questions.</strong><br/>It is to be used from within your theme template files:<br/>
		<?php highlight_string('<?php getFAQ(); ?>'); ?><br/><br/>
		<strong>You can also pass a catgory name as an Arguement.</strong><br/>Doing this will only display that category and its questions<br/>
		<?php highlight_string('<?php getFAQ(\'Your Category Name\'); ?>'); ?><br/><br/><br/>
		<strong>To show a category of FAQ from within a page use the following coding:</strong><br/>
		This will show posts on your page from the category you specify<br/>
		<pre>{$ Your Category Name $}</pre>
		<?php
	}
	
	/*********
	Front End Functions
	*********/
	
	public function viewFAQ()
	{
	
	}
	
	public function filterFAQ()
	{
	
	}

	public function viewCategories()
	{
	
	}
}


function FAQ_Admin()
{
	$FAQ = new FAQ;
	$FAQ->adminHeader();
	
	if(isset($_GET['add_faq']))
	{
		if(isset($_POST['add_new_faq']))
		{
			$FAQ->processFAQData();
		}
		$FAQ->showEditFAQ();
	}
	elseif(isset($_GET['edit_faq']))
	{
		if(isset($_POST['edit_faq']))
		{
			$FAQ->processFAQData($_POST['old-title']);
			$FAQ->showEditFAQ($_POST['title']);
		}
		else
		{
			$FAQ->showEditFAQ(urldecode($_GET['edit_faq']));
		}
	}
	elseif(isset($_GET['faq_categories']))
	{
		if(isset($_POST['new_category']))
		{
			$FAQ->processFAQData();
		}
		elseif(isset($_GET['delete_category']))
		{
			$FAQ->processFAQData(null,$_GET['delete_category']);
		}
		elseif(isset($_POST['edit_category_name']))
		{
			$FAQ->processFAQData(null,null,$_POST['edit_category_name']);
		}
		$FAQ->showEditCategory();
	}
	elseif(isset($_GET['faq_help']))
	{
		$FAQ->showHelp();
	}
	else
	{
		$FAQ->showViewAllFAQ();
	}
}

function filterPagesFAQ()
{

}

function getFAQData($display_category=null)
{
	$data_file = getXML(FAQFile);
	$end_result = '';
	foreach($data_file->category as $category)
	{
		$c_atts= $category->attributes();
		if($display_category == null)
		{
			$end_result .= '<ul><li>'.$c_atts['name'].'<ul>';
			
			foreach($category->content as $content)
			{
				$atts = $content->attributes();
				$end_result .= '<li>'.$atts['title'].'<ul><li>'.$content.'</li></ul></li>';
			}
			$end_result .= '</ul></li></ul>';
		}
		elseif($display_category == $c_atts['name'])
		{
			$end_result .= '<ul><li>'.$c_atts['name'].'<ul>';
			foreach($category->content as $content)
			{
				$atts = $content->attributes();
				$end_result .= '<li>'.$atts['title'].'<ul><li>'.$content.'</li></ul></li>';
			}
			$end_result .= '</ul></li></ul>';
		}
	}
	return $end_result;
}

function getFAQ($display_category=null)
{
	echo getFAQData($display_category);
}

function returnFAQ($display_category=null)
{
	if($display_category == null)
	{
		$display_category = '';
	}
	$end_result = getFAQData($display_category);
	return $end_result;
	
}

function faq_replace($content) 
{
	$the_callback = preg_match('/(<p>\s*)?{\$\s*([a-zA-Z0-9_]+)(\s+[^\$]+)?\s*\$}(\s*<\/p>)?/', $content, $matches);
	if(isset($matches[0]))
	{
		$display_category = str_replace('{$ ', '', $matches[0]);
		$display_category = str_replace(' $}', '', $display_category);
		$display_category = str_replace('<p>', '', $display_category);
		$display_category = str_replace('</p>', '', $display_category);
		$faq = returnFAQ($display_category);
		echo str_replace($matches[0],$faq,$content);
	}
	else
	{
		return $content;
	}
}
?>