<?php
/*
Plugin Name: User Login XML
Description: Allow users to create an account and login to your website. Protects pages. XML Based.
Version: 3.1
Author: Mike Henken
Author URI: http://michaelhenken.com/
*/

# get correct id for plugin
$thisfile=basename(__FILE__, ".php");
define('THISFILE_UL', $thisfile);

# add in this plugin's language file
i18n_merge(THISFILE_UL) || i18n_merge(THISFILE_UL, 'en_US');

# register plugin
register_plugin(
	$thisfile, 
	i18n_r(THISFILE_UL.'/PLUGIN_TITLE'), 
	'3.1', 			
	'Mike Henken',	
	'http://michaelhenken.com/', 
	i18n_r(THISFILE_UL.'/PLUGIN_DESC'), 
	'settings', 
	'user_login_admin' 
);

# hooks

//Adds 'Members Only' Check Box In edit.php
add_action('edit-extras','user_login_edit');

//Saves 'Members Only' checkbox selection when a page is saved
add_action('changedata-save','user_login_save');

//Filter Out The Content (Checks To See If Page Is Protected Or Not)
add_filter('content','perm_check');

//Launch Function 'user_login_check' before the template is loaded on the front-end
add_action('index-pretemplate','user_login_check');

//Add tab in admin navigation bar
add_action('nav-tab','makeNavTab');

//Define Feul Settings File
define('FeulFile', GSDATAOTHERPATH  . 'user-login.xml');

//Define User Login Plugin's plugins Folder (plugins/user_login/)
define('USERLOGINPATH', GSPLUGINPATH . 'user_login/');

//Define User Login Plugin's plugins includes Folder (plugins/user_login/inc/)
define('LOGININCPATH', GSPLUGINPATH . 'user_login/inc/');

//Define The User Data Storage Folder (data/site-users)
define('SITEUSERSPATH', GSDATAPATH . 'site-users/');



function makeNavTab()
{
	$plugin = 'user_login';
	$class = '';
	$txt = i18n_r(THISFILE_UL.'/PLUGIN_NAV');
	if (@$_GET['id'] == @$plugin) {
		$class='class="tabSelected"';
	}
	echo '<li><a href="load.php?id=', $plugin, '" ', $class, ' >';
	echo $txt;
	echo "</a></li>";
}	

//Include Feul class
require_once(USERLOGINPATH.'class/Feul.php');

/** 
* Check If Page Is Protected Or Not. 
* Then, Either Blocks Access Or Shows Content, Depending On If Logged In Or Not
*
* @param string $content the content for the page.. It comes through the content filter (hook)
*
* @return string content or page protected message
*/  
function perm_check($content)
{
	$Feul = new Feul;
	if($Feul->checkPerm() == true)
	{
		return $content;
	}
	else
	{
		return $Feul->getData('protectedmessage');
	}
}

/** 
* Proccess Admin User Login Settings and various contional statements related to this plugin.
*
* @return string content or page protected message
*/  
function user_login_admin()
{
	$Feul = new Feul;
	if(isset($Feul->first) && !isset($_GET['settings']))
	{
		echo '<div class="error"><a href="load.php?id=user_login&settings">', i18n_r(THISFILE_UL.'/CLICKHERE'), '</a> ', i18n_r(THISFILE_UL.'/CLICKHERETXT'), '</div>';
	}
?>	
	<link rel="stylesheet" type="text/css" href="../plugins/user_login/css/admin_style.css" />
	<div style="width:100%;margin:0 -15px -15px -10px;padding:0px;">
		<h3  class="floated"><?php i18n(THISFILE_UL.'/FEUL'); ?></h3>
		<div class="edit-nav clearfix">
			<p>
				<a href="load.php?id=user_login&help"><?php i18n('HELP'); ?></a>
				<a href="load.php?id=user_login&settings"><?php i18n('SETTINGS'); ?></a>
				<a href="load.php?id=user_login&email=yes"><?php i18n(THISFILE_UL.'/EMAIL_USERS'); ?></a>
				<a href="load.php?id=user_login&manage=yes"><?php i18n(THISFILE_UL.'/MANAGE_USERS'); ?></a>
			</p>
		</div>
	</div>
	</div>
	<div class="main" style="margin-top:-10px;">
	<?php
	if(isset($_GET['email']))
	{
		if(isset($_POST['send-email']))
		{	
			if(isset($_POST['send_all']))
			{
				if($Feul->Storage == 'XML')
				{
					$dir = SITEUSERSPATH."*.xml";
					// Make Edit Form For Each User XML File Found
					foreach (glob($dir) as $file) 
					{
						$xml = simplexml_load_file($file) or die( i18n_r(THISFILE_UL.'/UNABLE_LOADXML') );
						$Feul->processEmailUser($xml->EmailAddress, $xml->Username, $_POST['email'], $_POST['subject'], $_POST['post-email-message']);
					}
					echo '<div class="updated">', i18n_r(THISFILE_UL.'/EMAILS_SENT'), '</div>';
				}

				elseif($Feul->Storage == 'DB')
				{
					try 
					{
						$sql = "SELECT * FROM ".$this->DB_Table_Name;
						foreach ($this->dbh->query($sql) as $row)
						{
							$Feul->processEmailUser($row['EmailAddress'], $row['Username'], $_POST['email'], $_POST['subject'], $_POST['post-email-message']);
						}
						echo '<div class="updated">', i18n_r(THISFILE_UL.'/EMAILS_SENT'), '</div>';
					}
					catch(PDOException $e)
					{
						echo '<div class="error">', i18n_r(THISFILE_UL.'/ERROR'), $e->getMessage().'</div>';
					}
				}
			}
			else
			{
				$emails = $Feul->processEmailUser($_POST['email_to'], null, $_POST['email'], $_POST['subject'], $_POST['post-email-message']);
				if($emails != false)
				{
					echo '<div class="updated">', i18n_r(THISFILE_UL.'/EMAILS_SENT'), '</div>';
				}
			}
		}
	global $text_area_name;
	$text_area_name = 'post-email-message';
	?>
		<h3><?php i18n(THISFILE_UL.'/EMAIL_TITLE'); ?></h3>
		<form method="post" action="load.php?id=user_login&email=yes&send-email=yes">
			<p>
				<label for="from-email"><?php i18n(THISFILE_UL.'/FROM_LABEL'); ?></label>
				<input type="text" name="email" class="text" value="<?php echo $Feul->getData('email'); ?>" />
			</p>
			<div style="padding:10px;margin-bottom:15px;background-color:#f6f6f6;">
			<p>
				<label style="font-size:15px;padding-bottom:3px;"><?php i18n(THISFILE_UL.'/SEND_ALL_LABEL'); ?></label>
				<input type="checkbox" name="send_all" />
			</p>
			<p style="margin-top:-10px; margin-bottom:10px;">
				<label style="font-size:18px;color:red;"><?php i18n(THISFILE_UL.'/OR'); ?></label>
			</p>
			<p>
				<label for="subject" style="font-size:15px;padding-bottom:3px;"><?php i18n(THISFILE_UL.'/EMAILS_LABEL'); ?></label>
				<input type="text" name="email_to" class="text" value="" />
			</p>
			</div>
			<p>
				<label for="subject"><?php i18n(THISFILE_UL.'/SUBJECT_LABEL'); ?></label>
				<input type="text" name="subject" class="text" value="" />
			</p>
			<label for="email-message"><?php i18n(THISFILE_UL.'/MSG_LABEL'); ?></label>
			<textarea name="post-email-message"></textarea>
			<?php include(USERLOGINPATH . 'ckeditor.php'); ?>
			<input type="submit" class="submit" name="send-email" value="<?php i18n(THISFILE_UL.'/BTN_SUBMIT'); ?>" />
		</form>
	<?php
	}
	elseif(isset($_GET['settings']))
	{
		if(isset($_POST['storage']))
		{
			$submit_settings = $Feul->processSettings();
			if($submit_settings == true)
			{
				echo '<div class="updated">', i18n_r(THISFILE_UL.'/SAVE_SETTINGS_OK'), '</div>';
			}
			else
			{
				echo '<div class="error">', i18n_r(THISFILE_UL.'/SAVE_SETTINGS_NO'), '</div>';
			}
		}
		elseif(isset($_GET['create_db']))
		{	
			$create_db = $Feul->createDB();
			if($create_db != false)
			{
				echo '<div class="updated">', i18n_r(THISFILE_UL.'/DB_CREA_OK'), '</div>';
			}
		}
		elseif(isset($_GET['create_tb']))
		{
			$check_table = $Feul->checkTable();
			if($check_table == '1')
			{
				echo '<div class="error">', i18n_r(THISFILE_UL.'/DB_CREA_NO'), '</div>';
			}
			else
			{
				$create_table = $Feul->createDBTable();
				$check_table_again = $Feul->checkTable();
				if($check_table_again == '1')
				{
					echo '<div class="updated">', i18n_r(THISFILE_UL.'/DBT_CREA_OK'), '</div>';
				}
				elseif($check_table_again != 1)
				{
					echo '<div class="error">', i18n_r(THISFILE_UL.'/DBT_CREA_NO'), '</div>';
				}
			}
		}
		?>			
			<form method="post">
			<h2>Storage Settings</h2>
			<p>
				<label><?php i18n(THISFILE_UL.'/CHOOSE_STORAGE'); ?></label>
				<input type="radio" name="storage" value="XML" <?php if($Feul->Storage == 'XML') { echo ' CHECKED'; } ?> /> <?php i18n(THISFILE_UL.'/XML'); ?>
				<br/>
				<input type="radio" name="storage" value="DB" <?php if($Feul->Storage == 'DB') { echo ' CHECKED'; } ?> /> <?php i18n(THISFILE_UL.'/DATABASE'); ?>
			</p>
			
			<h4><?php i18n(THISFILE_UL.'/DB_SETTINGS'); ?></h4>
			<p>
				<label><?php i18n(THISFILE_UL.'/DB_HOST'); ?></label>
				<input type="text" class="text full" name="db_host" value="<?php if($Feul->DB_Host == '') { } else { echo $Feul->DB_Host; } ?>" />
			</p>
			<p>
				<label><?php i18n(THISFILE_UL.'/DB_USER'); ?></label>
				<input type="text" class="text full" name="db_user" value="<?php if($Feul->DB_User == '') { } else { echo $Feul->DB_User; } ?>" />
			</p>
			<p>
				<label><?php i18n(THISFILE_UL.'/DB_PWD'); ?></label>
				<input type="text" class="text full" name="db_pass" value="<?php if($Feul->DB_Pass == '') {  } else { echo $Feul->DB_Pass; } ?>" />
			</p>
			<p>
				<label><?php i18n(THISFILE_UL.'/DB_NAME'); ?></label>
				<?php i18n(THISFILE_UL.'/DB_NAMETXT'); ?>
				<input type="text" class="text full" name="db_name" value="<?php if($Feul->DB_Name == '') { echo ''; } else { echo $Feul->DB_Name; } ?>" />
			</p>
			<p>
				<label><?php i18n(THISFILE_UL.'/DBT_NAME'); ?></label>
				<?php i18n(THISFILE_UL.'/DBT_NAMETXT'); ?><br/>
				<input type="text" class="text full" name="db_table_name" value="<?php if($Feul->DB_Table_Name == '') { echo 'users'; } else { echo $Feul->DB_Table_Name; } ?>" />
			</p>
			<p>
				<label><?php i18n(THISFILE_UL.'/PDO_LABEL'); ?></label>
				<input type="radio" name="errors" value="On" <?php if($Feul->Errors == 'On') { echo ' CHECKED'; } ?> /> <?php i18n(THISFILE_UL.'/ENABLE'); ?>
				<br/>
				<input type="radio" name="errors" value="Off" <?php if($Feul->Errors == 'Off') { echo ' CHECKED'; } ?> /> <?php i18n(THISFILE_UL.'/DISABLE'); ?>
			</p>
			<p>
				<input type="submit" name="Feul_settings_form" class="submit" value="<?php i18n(THISFILE_UL.'/BTN_SUBMIT'); ?>" />
			</p>
			<p>
				<a href="load.php?id=user_login&settings&create_db"><?php i18n(THISFILE_UL.'/TEST_CREA_DB'); ?></a><br/>
				<a href="load.php?id=user_login&settings&create_tb"><?php i18n(THISFILE_UL.'/TEST_CREA_DBT'); ?></a>
			</p>
			</div>
			<div class="main" style="margin-top:-10px;">
				<h2><?php i18n(THISFILE_UL.'/EMAIL_SETTINGS'); ?></h2>
				<p>
					<label><?php i18n(THISFILE_UL.'/EMAIL_SLABEL'); ?></label><?php i18n(THISFILE_UL.'/EMAIL_SLABEL_TXT'); ?><br/>
					<input type="text" name="post-from-email" class="text full" value="<?php echo $Feul->getData('email'); ?>" />
				</p>
				
			</div>
			<div class="main" style="margin-top:-10px;">
			<h2><?php i18n(THISFILE_UL.'/CSS_TITLE'); ?></h2>
				<p>
					<label><?php i18n(THISFILE_UL.'/CSS_LABEL1'); ?></label>
					<textarea name="post-login-container" class="full" style="height:300px;">
						<?php echo $Feul->LoginCss; ?>
					</textarea>
				</p>
				<p>
					<label><?php i18n(THISFILE_UL.'/CSS_LABEL2'); ?></label>
					<textarea name="post-welcome-box" class="full" style="height:300px;">
						<?php echo $Feul->WelcomeCss; ?>
					</textarea>
				</p>
				<p>
					<label><?php i18n(THISFILE_UL.'/CSS_LABEL3'); ?></label>
					<textarea name="post-register-box" class="full" style="height:300px;">
						<?php echo $Feul->RegisterCss; ?>
					</textarea>
				</p>
				<p>
					<label><?php i18n(THISFILE_UL.'/MSG_LABEL2'); ?></label>
					<textarea name="post-protected-message">
						<?php global $text_area_name; $text_area_name = 'post-protected-message'; echo $Feul->ProtectedMessage; ?>
					</textarea>
					</p>
				<?php include(USERLOGINPATH . 'ckeditor.php'); ?>
				<p>
					<input type="submit" name="Feul_settings_form" class="submit" value="<?php i18n(THISFILE_UL.'/BTN_SUBMIT'); ?>" />
				</p>
			</form>
			<br/>
			<?php
	}
	elseif(isset($_GET['edit_user']))
	{
		if(isset($_POST['Feul_edit_user']))
		{
			if($_POST['old_name'] != $_POST['usernamec'])
			{
				$change_name = $_POST['usernamec'];
			}
			else
			{
				$change_name = null;
			}

			$posted_password = $_POST['nano'];	
			if(isset($_POST['userpassword']))
			{
				$change_pass = $_POST['userpassword'];
			}
			else
			{
				$change_pass = null;
			}
			
			
			if($Feul->Storage == 'XML')
			{
				$Feul->processEditUser($_POST['old_name'], $posted_password, $_POST['useremail'], $change_pass, $change_name);
			}
			elseif($Feul->Storage == 'DB')
			{
				$Feul->processEditDBUser($_POST['userID'], $_POST['usernamec'], $posted_password, $_POST['useremail']);
			}
			if($change_name != null)
			{
				print '<meta http-equiv="refresh" content="0;url=load.php?id=user_login&edit_user='.$change_name.'">';
			}
		}
		editUser($_GET['edit_user']);
	}
	elseif(isset($_GET['help']))
	{
		if(isset($_GET['convert']))
		{
			$convert = $Feul->convertXmlToDB();
			echo '<div class="updated">', i18n_r(THISFILE_UL.'/USR_CONV_OK'), '</div>';
		}
	?>
		<h2><?php i18n(THISFILE_UL.'/PLUGIN_INFOS'); ?></h2>

		<h4><?php i18n(THISFILE_UL.'/FUNCTIONS'); ?></h4>

		<p>
			<label><?php i18n(THISFILE_UL.'/DISPLAY_LABEL1'); ?></label>
			<?php highlight_string('<?php echo show_login_box(); ?>'); ?>
		</p>

		<p>
			<label><?php i18n(THISFILE_UL.'/DISPLAY_LABEL2'); ?></label>
			<?php i18n(THISFILE_UL.'/DISPLAY_L2TXT'); ?><br/>
			<?php highlight_string('<?php echo welcome_message_login(); ?>'); ?>
		</p>

		<p>
			<label><?php i18n(THISFILE_UL.'/DISPLAY_LABEL3'); ?></label>
			<?php highlight_string('<?php user_login_register(); ?>'); ?>
		</p>

		<h4><?php i18n(THISFILE_UL.'/SHOW_USR_ONLY'); ?></h4>
		<ol>
			<li><?php i18n(THISFILE_UL.'/SHOW_TXT1'); ?> <a href="load.php?id=user_login&settings"><?php i18n(THISFILE_UL.'/SHOW_HERE'); ?></a>
			</li><br/>
			<li>
				<?php i18n(THISFILE_UL.'/SHOW_TXT2'); ?><br/>
<pre>
<?php highlight_string('<?php if(!empty($_SESSION[\'LoggedIn\']))	{ ?>'); ?>
	Hello World
<?php highlight_string('<?php } ?>'); ?>
</pre>
			</li>
		</ol>

		<h4><?php i18n(THISFILE_UL.'/MORE_HELP'); ?></h4>
		<p>
			<?php i18n(THISFILE_UL.'/MORE_HELPTXT'); ?> <a href="http://get-simple.info/forum/topic/2342/front-end-user-login-plugin-xml-ver-25/"><?php i18n(THISFILE_UL.'/SHOW_HERE'); ?></a>
		</p>
		</div>

		<div class="main" style="margin-top:-10px;">
		<h2><?php i18n(THISFILE_UL.'/CONVERT_USERS'); ?></h2>
		<p>
			<?php i18n(THISFILE_UL.'/CONVERT_USRT'); ?><br/>
			<a href="load.php?id=user_login&help&convert"><?php i18n(THISFILE_UL.'/CONVERT_ULINK'); ?></a>
		</p>
	<?php
	}
	else
	{
		if(isset($_GET['manage']))
		{
			if(isset($_GET['adduser']))
			{		
				$Add_User = $Feul->processAddUserAdmin($_POST['usernamec'],$_POST['userpassword'],$_POST['useremail']);
				if($Add_User == false) 
				{
					echo '<div class="error">', i18n_r(THISFILE_UL.'/UNAME_EXISTS'), '</div>';
				}
				else
				{
					echo '<div class="updated">', i18n_r(THISFILE_UL.'/USER_ADDED'), '</div>';
				}
			}
			elseif (isset($_GET['deleteuser'])) 
			{
				if($Feul->Storage == 'XML')
				{
					$deleteUser = $Feul->deleteUser($_GET['deletename']);
				}			
				elseif($Feul->Storage == 'DB')
				{
					$deleteUser = $Feul->deleteUser($_GET['deleteuser']);
				}
				if($deleteUser == true)
				{
					echo '<div class="updated" style="display: block;">', $_GET['deletename'], i18n_r(THISFILE_UL.'/USER_DEL_OK'), '</div>';
				}
				else
				{
					echo '<div class="updated" style="display: block;"><span style="color:red;font-weight:bold;">', $_GET['deletename'], i18n_r(THISFILE_UL.'/USER_DEL_NO'), '</div>';
				}
			}
		}
		manageUsers();
	}
}

function manageUsers()
{
	$Feul = new Feul;
	$users = $Feul->getAllUsers();
	if($Feul->Storage == 'DB')
	{
		$users = (array) $users;
	}
		?>
		<div id="profile" class="hide-div section" style="display:none;margin-top:-30px;">
			<form method="post" action="load.php?id=user_login&manage=yes&adduser=yes">
				<h3><?php i18n(THISFILE_UL.'/ADD_USER'); ?></h3>
				<div class="leftsec">
					<p>
						<label for="usernamec" ><?php i18n(THISFILE_UL.'/UNAME'); ?></label>
						<input class="text" id="usernamec" name="usernamec" type="text" value="" />
					</p>
				</div>
				<div class="rightsec">
					<p>
						<label for="useremail" ><?php i18n(THISFILE_UL.'/UEMAIL'); ?></label>
						<input class="text" id="useremail" name="useremail" type="text" value="" />
					</p>
				</div>
				<div class="leftsec">
					<p>
						<label for="userpassword" ><?php i18n(THISFILE_UL.'/UPWD'); ?></label>
						<input autocomplete="off" class="text" id="userpassword" name="userpassword" type="text" value="" />
					</p>
				</div>
				<div class="clear"></div>
				<p id="submit_line" >
					<span>
						<input class="submit" type="submit" name="submitted" value="<?php i18n(THISFILE_UL.'/ADD_USER'); ?>" />
					</span> &nbsp;&nbsp;<?php i18n('OR'); ?>&nbsp;&nbsp; 
					<a class="cancel" href="#"><?php i18n('CANCEL'); ?></a>
				</p>
			</form>
		</div>
		<h3 class="floated"><?php i18n(THISFILE_UL.'/USER_MNGT'); ?></h3>
		<div class="edit-nav clearfix">
			<p>
				<a href="#" id="add-user"><?php i18n(THISFILE_UL.'/ADD_USER'); ?></a>
			</p>
		</div>
		<?php
		if($users != false) 
		{ 
		?>
			<table class="highlight" style="width:900px">
				<tr>
					<th><?php i18n(THISFILE_UL.'/NAME'); ?></th>
					<th><?php i18n(THISFILE_UL.'/EMAIL'); ?></th>
				<tbody>
			<?php
			// Make Edit Form For Each User XML File Found
			foreach ($users as $row)
			{
				if($Feul->Storage == 'XML')
				{
					$Username = $row->Username;
					$EmailAddress = $row->EmailAddress;
				}
				elseif($Feul->Storage == 'DB')
				{
					$userID =  $row['userID'];
					$Username = $row['Username'];
					$EmailAddress = $row['EmailAddress'];
				}

				//Below is the User Data
				?>	
				<tr>
					<td>
						<a href="load.php?id=user_login&edit_user=<?php if($Feul->Storage == 'XML') { echo $Username; } else { echo $userID; } ?>"><?php echo $Username; ?></a>
					</td>
					<td>
						<?php echo $EmailAddress; ?>
					</td>
				</tr>
			<?php } ?>
				</tbody>
			</table>
		<?php 
		}
		elseif($users == false)
		{
			echo '<p><strong>', i18n_r(THISFILE_UL.'/NO_USERS'), '</strong></p>';
		}
		?>
	<script type="text/javascript">
		
		/*** Show add-user form ***/
		$("#add-user").click(function () {
			$(".hide-div").show();
			$("#add-user").hide();
		});
		
		/*** Hide user form ***/
		$(".cancel").click(function () {
			$(".hide-div").hide();
			$("#add-user").show();
		});
	</script>
	<?php
}

function editUser($id)
{
	$id = urldecode($id);
	$Feul = new Feul;
	?>
	<h3>User Information</h3>
	<form method="post" action="load.php?id=user_login&edit_user=<?php echo $id; ?>">
		<div class="leftsec">
			<p>
				<label for="usernamec" ><?php i18n(THISFILE_UL.'/NAME2'); ?></label>
				<input class="text" id="usernamec" name="usernamec" type="text" value="<?php echo $Feul->getUserDataID($id,'Username'); ?>" />
			</p>
		</div>
		<div class="rightsec">
			<p>
				<label for="useremail" ><?php i18n(THISFILE_UL.'/UEMAIL'); ?></label>
				<input class="text" id="useremail" name="useremail" type="text" value="<?php echo $Feul->getUserDataID($id,'EmailAddress'); ?>" />
			</p>
		</div>
		<div class="leftsec">
			<p>
				<label for="userpassword" ><?php i18n(THISFILE_UL.'/CHANGE_PWD'); ?></label>
				<input autocomplete="off" class="text" id="userpassword" name="userpassword" type="text" value="" />
			</p>
		</div>
		<div class="clear"></div>
		<p id="submit_line">
			<span>
				<input class="submit" type="submit" name="Feul_edit_user" value="Submit Changes" /> &nbsp;&nbsp;<?php i18n('OR'); ?>&nbsp;&nbsp; <a class="cancel" style="color: #D94136;text-decoration:underline;cursor:pointer" ONCLICK="decision('<?php sprintf(i18n_r(THISFILE_UL.'/CONFIRM'), $Feul->getUserDataID($id,'Username')); ?>','load.php?id=user_login&manage=yes&deleteuser=<?php echo $id; ?>&deletename=<?php echo $Feul->getUserDataID($id,'Username'); ?>')"><?php i18n(THISFILE_UL.'/DEL_USER'); ?></a>
				<input type="hidden" name="nano" value="<?php echo $Feul->getUserDataID($id,'Password'); ?>"/>
				<input type="hidden" name="old_name" value="<?php echo $Feul->getUserDataID($id,'Username'); ?>"/>
				<input type="hidden" name="userID" value="<?php echo $id; ?>"/>
			</span>
		</p>
	</form>
	<script type="text/javascript">
		/*** Confirm the user wants to delete a user ***/
		function decision(message, url){
			if(confirm(message)) location.href = url;
		}
	</script>
	<?php
}


/*******
Function To: 
Displays Login Box On Front-End Of Website
*******/
function show_login_box()
{
	$Feul = new Feul;
	//If The User Is Not Logged In - Display Login Box - If They Are Logged In, Display Nothing
	if(!isset($_SESSION['LoggedIn']))
	{	
		echo $Feul->getData('logincontainer');
		$is_loggedIn = $Feul->checkLogin();
		//HTML Code For Login Container
		?>
		<div id="login_box" style="">
			<h2 class="login_h2"><?php i18n(THISFILE_UL.'/LOGIN'); ?></h2>
			<?php
				if(isset($_POST['username']) && isset($_POST['password']) && isset($_POST['login-form']) && $is_loggedIn == false)
				{
					echo '<div class="error">', i18n_r(THISFILE_UL.'/NOACCOUNT'), '</div>';
				}
			?>
			<form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>" name="loginform" id="loginform">
				<p>
					<label for="username"><?php i18n(THISFILE_UL.'/UNAME'); ?> </label>
					<input type="text" name="username" class="user_login_username" />
				</p>
				<p>
					<label for="username"><?php i18n(THISFILE_UL.'/UPWD'); ?> </label>
					<input type="password" name="password" class="user_login_password" />
				</p>
				<p>
					<input type="submit" class="user_login_submit" value="<?php i18n(THISFILE_UL.'/BTN_SUBMIT'); ?>"/>
				</p>
				<input type="hidden" name="login-form" value="login" />
			</form>
			<div style="clear:both"></div>
		</div>
		<?php
	}
}

/*******
Function To: 
Show Welcome Message If User Is Logged In - If Not Logged In, Display nothing
*******/
function welcome_message_login()
{
	global $SITEURL;
	$Feul = new Feul;
	if(isset($_SESSION['LoggedIn']))
	{
		$name  = $_SESSION['Username'];
		//Display Welcome Message
		$welcome_box = '<div class="user_login_welcome_box_container"><span class=\"user-login-welcome-label\">'. i18n_r(THISFILE_UL.'/WELCOME') .' </span>'.$name.'</div>';

		//Display Logout Link
		$logout_link = '<a href="'.$SITEURL.'?logout=yes" class="user-login-logout-link">'. i18n_r(THISFILE_UL.'/LOGOUT') .'</a>';
		echo $Feul->getData('welcomebox').$welcome_box.$logout_link ;
	}
}

/*******
Function To: 
Check If User Is Logged In - Also Starts Session And Connects To Database
*******/
function user_login_check()
{
	$Feul = new Feul;
	$Feul->checkLogin();
	/* 
	If Logout Link Is Clicked:
	Log Client Out (End Session) 
	*/
	if(isset($_GET['logout']))
	{
		if(!empty($_SESSION['LoggedIn']) && !empty($_SESSION['Username']))
		{
			$_SESSION = array(); 
			session_destroy();
		}
	}	
}


/*******
Function To: 
Register Form And Processing Code - Display's And Processes Register Form
*******/
function user_login_register()
{
	global $SITEURL;
	$Feul = new Feul;
	$error = '';
	//If User Is Not Logged In
	if(!isset($_SESSION['LoggedIn']))
	{
		if(isset($_POST['register-form']))
		{
			//If Register Form Was Submitted
			if($_POST['username'] != '' && $_POST['password'] != '' && $_POST['email'] != '')
			{		
				$addUser = $Feul->processAddUserAdmin($_POST['username'], $_POST['password'], $_POST['email']);
				if($addUser == true)
				{
					echo '<div class="success">', i18n_r(THISFILE_UL.'/ACCOUNT_SUCCESS'), '</div>';
					$Feul->checkLogin(true, $_POST['email'], $_POST['password']);
					//Send Email
					$to  = $_POST['email'];
					$Username = $_POST['username'];
					$chosen_password = $_POST['password'];

					// subject
					$subject = sprintf(i18n_r(THISFILE_UL.'/MSG_SUBJECT'), $Username);

					// message
					$message = '
					<html>
					<head>
		<meta http-equiv="content-type" content="text/html; charset='. i18n_r(THISFILE_UL.'/EMAIL_CHARSET') .'">
					<title>'. i18n_r(THISFILE_UL.'/MSG_TITLE') .'</title>
					</head>
					<body>
					<h2><strong>'. i18n_r(THISFILE_UL.'/MSG_TXTINFO') .'</strong></h2><br/><br/>
					<strong>'. i18n_r(THISFILE_UL.'/UNAME') .' </strong>'.$Username.'<br/>
					<strong>'. i18n_r(THISFILE_UL.'/UPWD') .' </strong>'.$chosen_password.'<br/>
					<br/>
					<a href="'.$SITEURL.'">'. i18n_r(THISFILE_UL.'/UPWD') .'</a>
					</body>
					</html>
					';

					// To send HTML mail, the Content-type header must be set
					$headers  = 'MIME-Version: 1.0' . "\r\n";
					$headers .= 'Content-type: text/html; charset='. i18n_r(THISFILE_UL.'/EMAIL_CHARSET') . "\r\n";

					// Additional headers
					//$headers .= 'To: Mary <mary@example.com>, Kelly <kelly@example.com>' . "\r\n";
					$headers .= 'From: '. i18n_r(THISFILE_UL.'/NEWACCOUNT') .' <'.$Feul->getData('email').'>' . "\r\n";
					//$headers .= 'Cc: birthdayarchive@example.com' . "\r\n";
					//$headers .= 'Bcc: birthdaycheck@example.com' . "\r\n";

					// Mail it
					$success = mail($to, $subject, $message, $headers);
					if(!$success)
					{
						$error = '<div class="error">'. i18n_r(THISFILE_UL.'/MSG_SEND_ERR') .'</div>';
					}
				}
				else
				{
					$error = '<div class="error">'. i18n_r(THISFILE_UL.'/USER_EXISTS') .'</div>';
				}
			}
			else
			{
				$error = '<div class="error">'. i18n_r(THISFILE_UL.'/FILL_FIELDS') .'</div>';
			}
		}
		echo $Feul->getData('registerbox');
		?>
			<?php echo $error; ?>
			<h2 class="register_h2"><?php i18n(THISFILE_UL.'/REGISTER'); ?></h2>
			<form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>" name="registerform" id="registerform">
				<p>
					<label for="username" class="required" ><?php i18n(THISFILE_UL.'/UNAME'); ?></label>
					<input type="text" class="required" name="username" id="name" />
				</p>
				<p>
					<label for="email" class="required" ><?php i18n(THISFILE_UL.'/UEMAIL'); ?></label>
					<input type="text" class="required" name="email" />
				</p>
				<p>
					<label for="password" class="required" ><?php i18n(THISFILE_UL.'/UPWD'); ?></label>
					<input type="password" class="required" name="password" id="password" />
				</p>
				<p>
					<input type="submit" name="register" id="register" value="<?php i18n(THISFILE_UL.'/BTN_REGISTER'); ?>" />
					<input type="hidden" name="register-form" value="yes" />
				</p>
			</form>
		<?php
	}	
}

//Displays members Only Checkbox In edit.php
function user_login_edit()
{
	$Feul = new Feul;
	$member_checkbox = '';
	if($Feul->showMembersPermBox() == true)
	{
		$member_checkbox = "checked";	
	}
	?>
			<p class="inline post-menu clearfix">
				<input type="checkbox" value="yes" name="member-only" id="member-only" style="width:20px;padding:0;margin:0;" <?php echo $member_checkbox; ?> />&nbsp;&nbsp;<label for="member-only"><?php i18n(THISFILE_UL.'/MEMBERS_ONLY'); ?></label>
			</p>
		<div class="clear"></div>
	<?php
}

//Saves Value Of Checkbox in function - user_login_edit()
function user_login_save()
{
	global $xml;
	if(isset($_POST['member-only']))
	{ 
		$node = $xml->addChild(strtolower('memberonly'))->addCData(stripslashes($_POST['member-only']));	
	}
}
