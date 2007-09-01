<?php

/*
$HeadURL$
$LastChangedRevision$
*/


if (!defined('TXP_INSTALL'))
	exit;

@ignore_user_abort(1);
@set_time_limit(0);

include_once(txpath.'/lib/mdb.php');
global $DB;
$DB =& mdb_factory($dhost,$ddb,$duser,$dpass,$dbcharset);

if ($DB->table_exists(PFX.'textpattern')) die("Textpattern database table already exist. Can't run setup.");

// FIXME: this stuff all belongs in mdb, if it's not there already
if (MDB_TYPE == 'pg') {
#	if (isset($dbcharset))
#		$DB->query('SET NAMES '.$dbcharset);

	$zerodate = '1970-01-01';
	$zerodatetime = $zerodate.' 00:00:00';
	$datetime = 'timestamp without time zone';
	$autoinc = 'SERIAL NOT NULL';
	$mediumtext = 'text';
	$tinytext = 'text';
	$tabletype = '';
	$incval = 'DEFAULT';
}
elseif(MDB_TYPE == 'my')  {
	$version = mysql_get_server_info();
	//Use "ENGINE" if version of MySQL > (4.0.18 or 4.1.2)
	$tabletype = ( intval($version[0]) >= 5 || preg_match('#^4\.(0\.[2-9]|(1[89]))|(1\.[2-9])#',$version))
					? " ENGINE=MyISAM "
					: " TYPE=MyISAM ";

	// On 4.1 or greater use utf8-tables
	if ( isset($dbcharset) && (intval($version[0]) >= 5 || preg_match('#^4\.[1-9]#',$version)))
	{
		$tabletype .= " CHARACTER SET = $dbcharset ";
		if (isset($dbcollate))
			$tabletype .= " COLLATE $dbcollate ";
#		$DB->query("SET NAMES ".$dbcharset);
	}

	$zerodate = '1970-01-01';
	$zerodatetime = $zerodate.' 00:00:00';
	$datetime = 'datetime';
	$autoinc = 'INT NOT NULL AUTO_INCREMENT';
	$incval = 'NULL';
	$mediumtext = 'mediumtext';
	$tinytext = 'tinytext';
}
elseif (MDB_TYPE == 'pdo_sqlite') {
	/*if (isset($dbcharset))
		db_query('SET NAMES '.$dbcharset);*/

	$zerodate = '1970-01-01';
	$zerodatetime = $zerodate.' 00:00:00';
	$datetime = 'datetime';
	$autoinc = 'INTEGER';
	$mediumtext = 'text';
	$tinytext = 'text';
	$tabletype = '';
	$incval = 'NULL';
	#asume sqlite will understand int and smallint as integers, due to INT afinity.
}

// Default to messy URLs if we know clean ones won't work
$permlink_mode = 'section_id_title';
if (is_callable('apache_get_modules')) {
	$modules = apache_get_modules();
	if (!in_array('mod_rewrite', $modules))
		$permlink_mode = 'messy';
}
else {
	$server_software = (@$_SERVER['SERVER_SOFTWARE'] || @$_SERVER['HTTP_HOST'])
		? ( (@$_SERVER['SERVER_SOFTWARE']) ?  @$_SERVER['SERVER_SOFTWARE'] :  $_SERVER['HTTP_HOST'] )
		: '';
   if (!stristr($server_software, 'Apache'))
		$permlink_mode = 'messy';
}

if (empty($name)) $name = 'anon';

$create_sql = array();

$create_sql[] = "CREATE TABLE ".PFX."textpattern (
  ID $autoinc,
  Posted $datetime NOT NULL default '$zerodatetime',
  AuthorID varchar(64) NOT NULL default '',
  LastMod $datetime NOT NULL default '$zerodatetime',
  LastModID varchar(64) NOT NULL default '',
  Title varchar(255) NOT NULL default '',
  Title_html varchar(255) NOT NULL default '',
  Body $mediumtext NOT NULL,
  Body_html $mediumtext NOT NULL,
  Excerpt text NOT NULL,
  Excerpt_html $mediumtext NOT NULL,
  Image varchar(255) NOT NULL default '',
  Category1 varchar(128) NOT NULL default '',
  Category2 varchar(128) NOT NULL default '',
  Annotate smallint NOT NULL default '0',
  AnnotateInvite varchar(255) NOT NULL default '',
  comments_count int NOT NULL default '0',
  Status smallint NOT NULL default '4',
  textile_body smallint NOT NULL default '1',
  textile_excerpt smallint NOT NULL default '1',
  Section varchar(64) NOT NULL default '',
  override_form varchar(255) NOT NULL default '',
  Keywords varchar(255) NOT NULL default '',
  url_title varchar(255) NOT NULL default '',
  custom_1 varchar(255) NOT NULL default '',
  custom_2 varchar(255) NOT NULL default '',
  custom_3 varchar(255) NOT NULL default '',
  custom_4 varchar(255) NOT NULL default '',
  custom_5 varchar(255) NOT NULL default '',
  custom_6 varchar(255) NOT NULL default '',
  custom_7 varchar(255) NOT NULL default '',
  custom_8 varchar(255) NOT NULL default '',
  custom_9 varchar(255) NOT NULL default '',
  custom_10 varchar(255) NOT NULL default '',
  uid varchar(32) NOT NULL default '',
  feed_time date NOT NULL default '$zerodate',
  PRIMARY KEY  (ID)
) $tabletype";
$create_sql[] = 'CREATE INDEX '.PFX.'categories_idx ON '.PFX.'textpattern (Category1,Category2)';
$create_sql[] = 'CREATE INDEX '.PFX.'Posted ON '.PFX.'textpattern (Posted)';
if (MDB_TYPE == 'my')
	$create_sql[] = 'CREATE FULLTEXT INDEX searching ON '.PFX.'textpattern (Title,Body)';

$setup_comment_invite = addslashes( ( gTxt('setup_comment_invite')=='setup_comment_invite') ? 'Comment' : gTxt('setup_comment_invite') );
$create_sql[] = "INSERT INTO ".PFX."textpattern VALUES ($incval, now(), '$name', now(), '', 'First Post', '', 'Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Donec rutrum est eu mauris. In volutpat blandit felis. Suspendisse eget pede. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos hymenaeos. Quisque sed arcu. Aenean purus nulla, condimentum ac, pretium at, commodo sit amet, turpis. Aenean lacus. Ut in justo. Ut viverra dui vel ante. Duis imperdiet porttitor mi. Maecenas at lectus eu justo porta tempus. Cras fermentum ligula non purus. Duis id orci non magna rutrum bibendum. Mauris tincidunt, massa in rhoncus consectetuer, lectus dui ornare enim, ut egestas ipsum purus id urna. Vestibulum volutpat porttitor metus. Donec congue vehicula ante.', '	<p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Donec rutrum est eu mauris. In volutpat blandit felis. Suspendisse eget pede. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos hymenaeos. Quisque sed arcu. Aenean purus nulla, condimentum ac, pretium at, commodo sit amet, turpis. Aenean lacus. Ut in justo. Ut viverra dui vel ante. Duis imperdiet porttitor mi. Maecenas at lectus eu justo porta tempus. Cras fermentum ligula non purus. Duis id orci non magna rutrum bibendum. Mauris tincidunt, massa in rhoncus consectetuer, lectus dui ornare enim, ut egestas ipsum purus id urna. Vestibulum volutpat porttitor metus. Donec congue vehicula ante.</p>\n\n\n ', '', '\n\n\n ', '', '', '', 1, '".$setup_comment_invite."', 1, 4, 1, 1, 'article', '', '', 'first-post', '', '', '', '', '', '', '', '', '', '', 'becfea8fd42801204463b23701199f28', now())";

$create_sql[] = "CREATE TABLE ".PFX."txp_category (
  id $autoinc,
  name varchar(64) NOT NULL default '',
  type varchar(64) NOT NULL default '',
  parent varchar(64) NOT NULL default '',
  lft int NOT NULL default '0',
  rgt int NOT NULL default '0',
  title varchar(255) NOT NULL default '',
  PRIMARY KEY  (id)
) $tabletype";

$create_sql[] = "INSERT INTO ".PFX."txp_category VALUES ($incval, 'root', 'article', '', 1, 8, 'root')";
$create_sql[] = "INSERT INTO ".PFX."txp_category VALUES ($incval, 'root', 'link', '', 1, 4, 'root')";
$create_sql[] = "INSERT INTO ".PFX."txp_category VALUES ($incval, 'root', 'image', '', 1, 4, 'root')";
$create_sql[] = "INSERT INTO ".PFX."txp_category VALUES ($incval, 'root', 'file', '', 1, 2, 'root')";
$create_sql[] = "INSERT INTO ".PFX."txp_category VALUES ($incval, 'reciprocal-affection', 'article', 'root', 6, 7, 'Reciprocal Affection')";
$create_sql[] = "INSERT INTO ".PFX."txp_category VALUES ($incval, 'hope-for-the-future', 'article', 'root', 2, 3, 'Hope for the Future')";
$create_sql[] = "INSERT INTO ".PFX."txp_category VALUES ($incval, 'meaningful-labor', 'article', 'root', 4, 5, 'Meaningful Labor')";
$create_sql[] = "INSERT INTO ".PFX."txp_category VALUES ($incval, 'textpattern', 'link', 'root', 2, 3, 'Textpattern')";
$create_sql[] = "INSERT INTO ".PFX."txp_category VALUES ($incval, 'site-design', 'image', 'root', 2, 3, 'Site Design')";


$create_sql[] = "CREATE TABLE ".PFX."txp_css (
  name varchar(255) default NULL,
  css text
) $tabletype ";

$create_sql[] = 'CREATE UNIQUE INDEX '.PFX.'name ON '.PFX.'txp_css (name)';

$create_sql[] = "INSERT INTO ".PFX."txp_css VALUES ('default', 'LyogYmFzZQ0KLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0gKi8NCg0KYm9keSB7DQptYXJnaW46IDA7DQpwYWRkaW5nOiAwOw0KZm9udC1mYW1pbHk6IFZlcmRhbmEsICJMdWNpZGEgR3JhbmRlIiwgVGFob21hLCBIZWx2ZXRpY2EsIHNhbnMtc2VyaWY7DQpjb2xvcjogIzAwMDsNCmJhY2tncm91bmQtY29sb3I6ICNmZmY7DQp9DQoNCmJsb2NrcXVvdGUsIGgzLCBwLCBsaSB7DQpwYWRkaW5nLXJpZ2h0OiAxMHB4Ow0KcGFkZGluZy1sZWZ0OiAxMHB4Ow0KZm9udC1zaXplOiAwLjllbTsNCmxpbmUtaGVpZ2h0OiAxLjZlbTsNCn0NCg0KYmxvY2txdW90ZSB7DQptYXJnaW4tcmlnaHQ6IDA7DQptYXJnaW4tbGVmdDogMjBweDsNCn0NCg0KaDEsIGgyLCBoMyB7DQpmb250LXdlaWdodDogbm9ybWFsOw0KfQ0KDQpoMSwgaDIgew0KZm9udC1mYW1pbHk6IEdlb3JnaWEsIFRpbWVzLCBzZXJpZjsNCn0NCg0KaDEgew0KZm9udC1zaXplOiAzZW07DQp9DQoNCmgyIHsNCmZvbnQtc2l6ZTogMWVtOw0KZm9udC1zdHlsZTogaXRhbGljOw0KfQ0KDQpociB7DQptYXJnaW46IDJlbSBhdXRvOw0Kd2lkdGg6IDM3MHB4Ow0KaGVpZ2h0OiAxcHg7DQpjb2xvcjogIzdhN2U3ZDsNCmJhY2tncm91bmQtY29sb3I6ICM3YTdlN2Q7DQpib3JkZXI6IG5vbmU7DQp9DQoNCnNtYWxsLCAuc21hbGwgew0KZm9udC1zaXplOiAwLjllbTsNCn0NCg0KLyogbGlua3MNCi0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tICovDQoNCmEgew0KdGV4dC1kZWNvcmF0aW9uOiBub25lOw0KY29sb3I6ICMwMDA7DQpib3JkZXItYm90dG9tOiAxcHggIzAwMCBzb2xpZDsNCn0NCg0KaDEgYSwgaDIgYSwgaDMgYSB7DQpib3JkZXI6IG5vbmU7DQp9DQoNCmgzIGEgew0KZm9udDogMS41ZW0gR2VvcmdpYSwgVGltZXMsIHNlcmlmOw0KfQ0KDQojc2lkZWJhci0yIGEsICNzaWRlYmFyLTEgYSB7DQpjb2xvcjogI2MwMDsNCmJvcmRlcjogbm9uZTsNCn0NCg0KLyogb3ZlcnJpZGVzDQotLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLSAqLw0KDQojc2lkZWJhci0yIHAsICNzaWRlYmFyLTEgcCB7DQpmb250LXNpemU6IDAuOGVtOw0KbGluZS1oZWlnaHQ6IDEuNWVtOw0KfQ0KDQouY2FwcyB7DQpmb250LXNpemU6IDAuOWVtOw0KbGV0dGVyLXNwYWNpbmc6IDAuMWVtOw0KfQ0KDQpkaXYuZGl2aWRlciB7DQptYXJnaW46IDJlbSAwOw0KdGV4dC1hbGlnbjogY2VudGVyOw0KfQ0KDQovKiBsYXlvdXQNCi0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tICovDQoNCiNhY2Nlc3NpYmlsaXR5IHsNCnBvc2l0aW9uOiBhYnNvbHV0ZTsNCnRvcDogLTEwMDAwcHg7DQp9DQoNCiNjb250YWluZXIgew0KbWFyZ2luOiAxMHB4IGF1dG87DQpwYWRkaW5nOiAxMHB4Ow0Kd2lkdGg6IDc2MHB4Ow0KfQ0KDQojaGVhZCB7DQpoZWlnaHQ6IDEwMHB4Ow0KdGV4dC1hbGlnbjogY2VudGVyOw0KfQ0KDQojc2lkZWJhci0xLCAjc2lkZWJhci0yIHsNCnBhZGRpbmctdG9wOiAxMDBweDsNCndpZHRoOiAxNTBweDsNCn0NCg0KI3NpZGViYXItMSB7DQptYXJnaW4tcmlnaHQ6IDVweDsNCmZsb2F0OiBsZWZ0Ow0KdGV4dC1hbGlnbjogcmlnaHQ7DQp9DQoNCiNzaWRlYmFyLTIgew0KbWFyZ2luLWxlZnQ6IDVweDsNCmZsb2F0OiByaWdodDsNCn0NCg0KI2NvbnRlbnQgew0KbWFyZ2luOiAwIDE1NXB4Ow0KcGFkZGluZy10b3A6IDMwcHg7DQp9DQoNCiNmb290IHsNCm1hcmdpbi10b3A6IDVweDsNCmNsZWFyOiBib3RoOw0KdGV4dC1hbGlnbjogY2VudGVyOw0KfQ0KDQovKiBib3ggbW9kZWwgaGFja3MNCmh0dHA6Ly9hcmNoaXZpc3QuaW5jdXRpby5jb20vdmlld2xpc3QvY3NzLWRpc2N1c3MvNDgzODYNCi0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tICovDQoNCiNjb250YWluZXIgew0KXHdpZHRoOiA3NzBweDsNCndcaWR0aDogNzYwcHg7DQp9DQoNCiNzaWRlYmFyLTEsICNzaWRlYmFyLTIgew0KXHdpZHRoOiAxNTBweDsNCndcaWR0aDogMTUwcHg7DQp9DQoNCi8qIGNvbW1lbnRzDQotLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLSAqLw0KDQouY29tbWVudHNfZXJyb3Igew0KY29sb3I6ICMwMDA7DQpiYWNrZ3JvdW5kLWNvbG9yOiAjZmZmNGY0IA0KfQ0KDQp1bC5jb21tZW50c19lcnJvciB7DQpwYWRkaW5nIDogMC4zZW07DQpsaXN0LXN0eWxlLXR5cGU6IGNpcmNsZTsNCmxpc3Qtc3R5bGUtcG9zaXRpb246IGluc2lkZTsNCmJvcmRlcjogMnB4IHNvbGlkICNmZGQ7DQp9DQoNCmRpdiNjcHJldmlldyB7DQpjb2xvcjogIzAwMDsNCmJhY2tncm91bmQtY29sb3I6ICNmMWYxZjE7DQpib3JkZXI6IDJweCBzb2xpZCAjZGRkOw0KfQ0KDQpmb3JtI3R4cENvbW1lbnRJbnB1dEZvcm0gdGQgew0KdmVydGljYWwtYWxpZ246IHRvcDsNCn0=')";

$create_sql[] = "CREATE TABLE ".PFX."txp_discuss (
  discussid $autoinc,
  parentid int NOT NULL default '0',
  name varchar(255) NOT NULL default '',
  email varchar(50) NOT NULL default '',
  web varchar(255) NOT NULL default '',
  ip varchar(100) NOT NULL default '',
  posted $datetime NOT NULL default '$zerodatetime',
  message text NOT NULL,
  visible smallint NOT NULL default '1',
  PRIMARY KEY  (discussid)
) $tabletype";

$create_sql[] = 'CREATE INDEX '.PFX.'parentid ON '.PFX.'txp_discuss (parentid)';

$create_sql[] = "INSERT INTO ".PFX."txp_discuss VALUES (000001, 1, 'Donald Swain', 'me@here.com', 'example.com', '127.0.0.1', '2005-07-22 14:11:32', '<p>I enjoy your site very much.</p>', 1)";

$create_sql[] = "CREATE TABLE ".PFX."txp_discuss_ipban (
  ip varchar(255) NOT NULL default '',
  name_used varchar(255) NOT NULL default '',
  date_banned $datetime NOT NULL default '$zerodatetime',
  banned_on_message smallint NOT NULL default '0',
  PRIMARY KEY (ip)
) $tabletype ";

$create_sql[] = "CREATE TABLE ".PFX."txp_discuss_nonce (
  issue_time $datetime NOT NULL default '$zerodatetime',
  nonce varchar(255) NOT NULL default '',
  used smallint NOT NULL default '0',
  secret varchar(255) NOT NULL default '',
  PRIMARY KEY (nonce)
) $tabletype ";

$create_sql[] = "CREATE TABLE ".PFX."txp_file (
  id $autoinc,
  filename varchar(255) NOT NULL default '',
  category varchar(255) NOT NULL default '',
  permissions varchar(32) NOT NULL default '0',
  description text NOT NULL,
  downloads int NOT NULL default '0',
  PRIMARY KEY (id)
) $tabletype";

$create_sql[] = 'CREATE UNIQUE INDEX '.PFX.'filename ON '.PFX.'txp_file (filename)';

$create_sql[] = "CREATE TABLE ".PFX."txp_form (
  name varchar(64) NOT NULL default '',
  type varchar(28) NOT NULL default '',
  Form text NOT NULL,
  PRIMARY KEY (name)
) $tabletype";

$create_sql[] = "INSERT INTO ".PFX."txp_form VALUES ('Links', 'link', '<p><txp:link /><br />\n<txp:link_description /></p>')";
$create_sql[] = "INSERT INTO ".PFX."txp_form VALUES ('lofi', 'article', '<h3><txp:title /></h3>\n\n<p class=\"small\"><txp:permlink>#</txp:permlink> <txp:posted /></p>\n\n<txp:body />\n\n<hr />')";
$create_sql[] = "INSERT INTO ".PFX."txp_form VALUES ('single', 'article', '<h3><txp:title /> <span class=\"permlink\"><txp:permlink>::</txp:permlink></span> <span class=\"date\"><txp:posted /></span></h3>\n\n<txp:body />')";
$create_sql[] = "INSERT INTO ".PFX."txp_form VALUES ('plainlinks', 'link', '<txp:linkdesctitle /><br />')";
$create_sql[] = "INSERT INTO ".PFX."txp_form VALUES ('comments', 'comment', '<txp:comment_message />\n\n<p class=\"small\">&#8212; <txp:comment_name /> &#183; <txp:comment_time /> &#183; <txp:comment_permlink>#</txp:comment_permlink></p>')";
$create_sql[] = "INSERT INTO ".PFX."txp_form VALUES ('default', 'article', '<h3><txp:permlink><txp:title /></txp:permlink> &#183; <txp:posted /> by <txp:author /></h3>\n\n<txp:body />\n\n<txp:comments_invite wraptag=\"p\" />\n\n<div class=\"divider\"><img src=\"<txp:site_url />images/1.gif\" width=\"400\" height=\"1\" alt=\"---\" title=\"\" /></div>')";
$create_sql[] = "INSERT INTO ".PFX."txp_form VALUES ('comment_form', 'comment', '<table cellpadding=\"4\" cellspacing=\"0\" border=\"0\">\n\n<tr>\n\t<td align=\"right\">\n\t\t<label for=\"name\"><txp:text item=\"name\" /></label>\n\t</td>\n\n\t<td>\n\t\t<txp:comment_name_input />\n\t</td>\n\n\t<td>\n\t\t<txp:comment_remember />\n\t</td> \n</tr>\n\n<tr>\n\t<td align=\"right\">\n\t\t<label for=\"email\"><txp:text item=\"email\" /></label>\n\t</td>\n\n\t<td colspan=\"2\">\n\t\t<txp:comment_email_input />\n\t</td>\n</tr>\n\n<tr> \n\t<td align=\"right\">\n\t\t<label for=\"web\">http://</label>\n\t</td>\n\n\t<td colspan=\"2\">\n\t\t<txp:comment_web_input />\n\t</td>\n</tr>\n\n<tr>\n\t<td align=\"right\">\n\t\t<label for=\"message\"><txp:text item=\"message\" /></label>\n\t</td>\n\n\t<td colspan=\"2\">\n\t\t<txp:comment_message_input />\n\t</td>\n</tr>\n\n<tr>\n\t<td align=\"right\">&nbsp;</td>\n\n\t<td>\n\t\t<txp:comments_help />\n\t</td>\n\n\t<td align=\"right\">\n\t\t<txp:comment_preview />\n\t\t<txp:comment_submit />\n\t</td>\n</tr>\n\n</table>')";
$create_sql[] = "INSERT INTO ".PFX."txp_form VALUES ('noted', 'link', '<p><txp:link />. <txp:link_description /></p>')";
$create_sql[] = "INSERT INTO ".PFX."txp_form VALUES ('popup_comments', 'comment', '<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" lang=\"en\">\n<head>\n\t<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\n\t<title><txp:page_title /></title>\n\t<link rel=\"stylesheet\" type=\"text/css\" href=\"<txp:css />\" />\n</head>\n<body>\n\n<div style=\"padding: 1em; width:300px;\">\n<txp:popup_comments />\n</div>\n\n</body>\n</html>')";
$create_sql[] = "INSERT INTO ".PFX."txp_form VALUES ('files', 'file', '<txp:text item=\"file\" />: \n<txp:file_download_link>\n<txp:file_download_name /> [<txp:file_download_size format=\"auto\" decimals=\"2\" />]\n</txp:file_download_link>\n<br />\n<txp:text item=\"category\" />: <txp:file_download_category /><br />\n<txp:text item=\"download\" />: <txp:file_download_downloads />')";
$create_sql[] = "INSERT INTO ".PFX."txp_form VALUES ('search_results', 'article', '<h3><txp:search_result_permlink><txp:search_result_title /></txp:search_result_permlink></h3>\n\n<p><txp:search_result_excerpt /></p>\n\n<p class=\"small\"><txp:search_result_permlink><txp:search_result_permlink /></txp:search_result_permlink> &#183; \n\t<txp:search_result_date /></p>')";
$create_sql[] = "INSERT INTO ".PFX."txp_form VALUES ('comments_display', 'article', '<h3 id=\"comment\"><txp:comments_invite textonly=\"1\" showalways=\"1\" showcount=\"0\" /></h3>\n\n<txp:comments />\n\n<txp:if_comments_preview>\n<div id=\"cpreview\">\n<txp:comments_preview />\n</div>\n</txp:if_comments_preview>\n\n<txp:if_comments_allowed>\n<txp:comments_form preview=\"1\" />\n<txp:else />\n<p><txp:text item=\"comments_closed\" /></p>\n</txp:if_comments_allowed>')";

$create_sql[] = "CREATE TABLE ".PFX."txp_image (
  id $autoinc,
  name varchar(255) NOT NULL default '',
  category varchar(255) NOT NULL default '',
  ext varchar(20) NOT NULL default '',
  w int NOT NULL default '0',
  h int NOT NULL default '0',
  alt varchar(255) NOT NULL default '',
  caption text NOT NULL,
  date $datetime NOT NULL default '$zerodatetime',
  author varchar(255) NOT NULL default '',
  thumbnail smallint NOT NULL default '0',
  PRIMARY KEY  (id)
) $tabletype";

$create_sql[] = "CREATE TABLE ".PFX."txp_lang (
  id $autoinc,
  lang varchar(16) default NULL,
  name varchar(64) default NULL,
  event varchar(64) default NULL,
  data $tinytext,
  lastmod timestamp,
  PRIMARY KEY  (id)
) $tabletype";

$create_sql[] = 'CREATE UNIQUE INDEX '.PFX.'lang ON '.PFX.'txp_lang (lang,name)';
$create_sql[] = 'CREATE INDEX '.PFX.'lang_2 ON '.PFX.'txp_lang (lang,event)';

$create_sql[] = "CREATE TABLE ".PFX."txp_link (
  id $autoinc,
  date $datetime NOT NULL default '$zerodatetime',
  category varchar(64) NOT NULL default '',
  url text NOT NULL,
  linkname varchar(255) NOT NULL default '',
  linksort varchar(128) NOT NULL default '',
  description text NOT NULL,
  PRIMARY KEY  (id)
) $tabletype";

$create_sql[] = "INSERT INTO ".PFX."txp_link VALUES (1, '2005-07-20 12:54:26', 'textpattern', 'http://textpattern.com/', 'Textpattern', 'Textpattern', '')";
$create_sql[] = "INSERT INTO ".PFX."txp_link VALUES (2, '2005-07-20 12:54:41', 'textpattern', 'http://textpattern.net/', 'TextBook', 'TextBook', '')";
$create_sql[] = "INSERT INTO ".PFX."txp_link VALUES (3, '2005-07-20 12:55:04', 'textpattern', 'http://textpattern.org/', 'Txp Resources', 'Txp Resources', '')";

$create_sql[] = "CREATE TABLE ".PFX."txp_log (
  id $autoinc,
  time $datetime NOT NULL default '$zerodatetime',
  host varchar(255) NOT NULL default '',
  page varchar(255) NOT NULL default '',
  refer text NOT NULL,
  status int NOT NULL default '200',
  method varchar(16) NOT NULL default 'GET',
  ip varchar(16) NOT NULL default '',
  PRIMARY KEY  (id)
) $tabletype ";

$create_sql[] = 'CREATE INDEX '.PFX.'time ON '.PFX.'txp_log (time)';

$create_sql[] = "CREATE TABLE ".PFX."txp_page (
  name varchar(128) NOT NULL default '',
  user_html text NOT NULL,
  PRIMARY KEY (name)
) $tabletype";

$create_sql[] = "INSERT INTO ".PFX."txp_page VALUES ('default', '<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" lang=\"en\">\n<head>\n\t<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\n\n\t<title><txp:page_title /></title>\n\n\t<txp:feed_link flavor=\"atom\" format=\"link\" label=\"Atom\" />\n\t<txp:feed_link flavor=\"rss\" format=\"link\" label=\"RSS\" />\n\n\t<txp:css format=\"link\" />\n</head>\n<body>\n\n<!-- accessibility -->\n<div id=\"accessibility\">\n\t<ul>\n\t\t<li><a href=\"#content\">Go to content</a></li>\n\t\t<li><a href=\"#sidebar-1\">Go to navigation</a></li>\n\t\t<li><a href=\"#sidebar-2\">Go to search</a></li>\n\t</ul>\n</div>\n\n<div id=\"container\">\n\n<!-- head -->\n\t<div id=\"head\">\n\t\t<h1><txp:link_to_home><txp:sitename /></txp:link_to_home></h1>\n\t\t<h2><txp:site_slogan /></h2>\n\t</div>\n\n<!-- left -->\n\t<div id=\"sidebar-1\">\n\t<txp:linklist wraptag=\"p\" />\n\t</div>\n\n<!-- right -->\n\t<div id=\"sidebar-2\">\n\t\t<txp:search_input label=\"Search\" wraptag=\"p\" />\n\n\t\t<txp:popup type=\"c\" label=\"Browse\" wraptag=\"p\" />\n\n\t\t<p><txp:feed_link label=\"RSS\" /> / <txp:feed_link flavor=\"atom\" label=\"Atom\" /></p>\n\n\t\t<p><img src=\"<txp:site_url />textpattern/txp_img/txp_slug105x45.gif\" width=\"105\" height=\"45\" alt=\"Textpattern\" title=\"\" /></p>\n\t</div>\n\n<!-- center -->\n\t<div id=\"content\">\n\t<txp:article limit=\"5\" />\n\t\n<txp:if_individual_article>\n\t\t<p><txp:link_to_prev><txp:prev_title /></txp:link_to_prev> \n\t\t\t<txp:link_to_next><txp:next_title /></txp:link_to_next></p>\n<txp:else />\n\t\t<p><txp:older><txp:text item=\"older\" /></txp:older> \n\t\t\t<txp:newer><txp:text item=\"newer\" /></txp:newer></p>\n</txp:if_individual_article>\n\t</div>\n\n<!-- footer -->\n\t<div id=\"foot\">&nbsp;</div>\n\n</div>\n\n</body>\n</html>')";
$create_sql[] = "INSERT INTO ".PFX."txp_page VALUES ('archive', '<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" lang=\"en\">\n<head>\n\t<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\n\n\t<title><txp:page_title /></title>\n\n\t<txp:feed_link flavor=\"atom\" format=\"link\" label=\"Atom\" />\n\t<txp:feed_link flavor=\"rss\" format=\"link\" label=\"RSS\" />\n\n\t<txp:css format=\"link\" />\n</head>\n<body>\n\n<!-- accessibility -->\n<div id=\"accessibility\">\n\t<ul>\n\t\t<li><a href=\"#content\">Go to content</a></li>\n\t\t<li><a href=\"#sidebar-1\">Go to navigation</a></li>\n\t\t<li><a href=\"#sidebar-2\">Go to search</a></li>\n\t</ul>\n</div>\n\n<div id=\"container\">\n\n<!-- head -->\n\t<div id=\"head\">\n\t\t<h1><txp:link_to_home><txp:sitename /></txp:link_to_home></h1>\n\t\t<h2><txp:site_slogan /></h2>\n\t</div>\n\n<!-- left -->\n\t<div id=\"sidebar-1\">\n\t<txp:linklist wraptag=\"p\" />\n\t</div>\n\n<!-- right -->\n\t<div id=\"sidebar-2\">\n\t\t<txp:search_input label=\"Search\" wraptag=\"p\" />\n\n\t\t<txp:popup type=\"c\" label=\"Browse\" wraptag=\"p\" />\n\n\t\t<p><txp:feed_link label=\"RSS\" /> / <txp:feed_link flavor=\"atom\" label=\"Atom\" /></p>\n\n\t\t<p><img src=\"<txp:site_url />textpattern/txp_img/txp_slug105x45.gif\" width=\"105\" height=\"45\" alt=\"Textpattern\" title=\"\" /></p>\n\t</div>\n\n<!-- center -->\n\t<div id=\"content\">\n\t<txp:article limit=\"5\" />\n\t\n<txp:if_individual_article>\n\t\t<p><txp:link_to_prev><txp:prev_title /></txp:link_to_prev> \n\t\t\t<txp:link_to_next><txp:next_title /></txp:link_to_next></p>\n<txp:else />\n\t\t<p><txp:older><txp:text item=\"older\" /></txp:older> \n\t\t\t<txp:newer><txp:text item=\"newer\" /></txp:newer></p>\n</txp:if_individual_article>\n\t</div>\n\n<!-- footer -->\n\t<div id=\"foot\">&nbsp;</div>\n\n</div>\n\n</body>\n</html>')";

$create_sql[] = "CREATE TABLE ".PFX."txp_plugin (
  name varchar(64) NOT NULL default '',
  status smallint NOT NULL default '1',
  author varchar(128) NOT NULL default '',
  author_uri varchar(128) NOT NULL default '',
  version varchar(10) NOT NULL default '1.0',
  description text NOT NULL,
  help text NOT NULL,
  code text NOT NULL,
  code_restore text NOT NULL,
  code_md5 varchar(32) NOT NULL default '',
  type smallint NOT NULL default '0',
  PRIMARY KEY (name)
) $tabletype ";


$create_sql[] = "CREATE TABLE ".PFX."txp_prefs (
  prefs_id INT NOT NULL default '1',
  name varchar(255) default NULL,
  val varchar(255) default NULL,
  type smallint NOT NULL default '2',
  event varchar(12) NOT NULL default 'publish',
  html varchar(64) NOT NULL default 'text_input',
  position smallint NOT NULL default '0',
  PRIMARY KEY (prefs_id, name)
) $tabletype ";

$create_sql[] = 'CREATE UNIQUE INDEX '.PFX.'prefs_idx ON '.PFX.'txp_prefs (prefs_id,name)';
$create_sql[] = 'CREATE INDEX '.PFX.'name ON '.PFX.'txp_prefs (name)';

$prefs['blog_uid'] = md5(uniqid(rand(),true));
$create_sql[] = "INSERT INTO ".PFX."txp_prefs VALUES (1, 'prefs_id', '1', 2, 'publish', 'text_input', 0)";
$create_sql[] = "INSERT INTO ".PFX."txp_prefs VALUES (1, 'sitename', '".addslashes(gTxt('my_site'))."', 0, 'publish', 'text_input', 10)";
$create_sql[] = "INSERT INTO ".PFX."txp_prefs VALUES (1, 'siteurl', 'comment.local', 0, 'publish', 'text_input', 20)";
$create_sql[] = "INSERT INTO ".PFX."txp_prefs VALUES (1, 'site_slogan', '".addslashes(gTxt('my_slogan'))."', 0, 'publish', 'text_input', 30)";
$create_sql[] = "INSERT INTO ".PFX."txp_prefs VALUES (1, 'language', 'en-gb', 2, 'publish', 'languages', 40)";
$create_sql[] = "INSERT INTO ".PFX."txp_prefs VALUES (1, 'url_mode', '1', 2, 'publish', 'text_input', 0)";
$create_sql[] = "INSERT INTO ".PFX."txp_prefs VALUES (1, 'timeoffset', '0', 2, 'publish', 'text_input', 0)";
$create_sql[] = "INSERT INTO ".PFX."txp_prefs VALUES (1, 'comments_on_default', '0', 0, 'comments', 'yesnoradio', 140)";
$create_sql[] = "INSERT INTO ".PFX."txp_prefs VALUES (1, 'comments_default_invite', '".$setup_comment_invite."', 0, 'comments', 'text_input', 180)";
$create_sql[] = "INSERT INTO ".PFX."txp_prefs VALUES (1, 'comments_mode', '0', 0, 'comments', 'commentmode', 200)";
$create_sql[] = "INSERT INTO ".PFX."txp_prefs VALUES (1, 'comments_disabled_after', '42', 0, 'comments', 'weeks', 210)";
$create_sql[] = "INSERT INTO ".PFX."txp_prefs VALUES (1, 'use_textile', '2', 0, 'publish', 'pref_text', 110)";
$create_sql[] = "INSERT INTO ".PFX."txp_prefs VALUES (1, 'ping_weblogsdotcom', '0', 1, 'publish', 'yesnoradio', 0)";
$create_sql[] = "INSERT INTO ".PFX."txp_prefs VALUES (1, 'rss_how_many', '5', 1, 'admin', 'text_input', 0)";
$create_sql[] = "INSERT INTO ".PFX."txp_prefs VALUES (1, 'logging', 'all', 0, 'publish', 'logging', 100)";
$create_sql[] = "INSERT INTO ".PFX."txp_prefs VALUES (1, 'use_comments', '1', 0, 'publish', 'yesnoradio', 120)";
$create_sql[] = "INSERT INTO ".PFX."txp_prefs VALUES (1, 'use_categories', '1', 2, 'publish', 'text_input', 0)";
$create_sql[] = "INSERT INTO ".PFX."txp_prefs VALUES (1, 'use_sections', '1', 2, 'publish', 'text_input', 0)";
$create_sql[] = "INSERT INTO ".PFX."txp_prefs VALUES (1, 'send_lastmod', '0', 1, 'admin', 'yesnoradio', 0)";
$create_sql[] = "INSERT INTO ".PFX."txp_prefs VALUES (1, 'lastmod', '2005-07-23 16:24:10', 2, 'publish', 'text_input', 0)";
$create_sql[] = "INSERT INTO ".PFX."txp_prefs VALUES (1, 'comments_dateformat', '%b %d, %I:%M %p', 0, 'comments', 'dateformats', 190)";
$create_sql[] = "INSERT INTO ".PFX."txp_prefs VALUES (1, 'dateformat', 'since', 0, 'publish', 'dateformats', 70)";
$create_sql[] = "INSERT INTO ".PFX."txp_prefs VALUES (1, 'archive_dateformat', '%b %d, %I:%M %p', 0, 'publish', 'dateformats', 80)";
$create_sql[] = "INSERT INTO ".PFX."txp_prefs VALUES (1, 'comments_moderate', '1', 0, 'comments', 'yesnoradio', 130)";
$create_sql[] = "INSERT INTO ".PFX."txp_prefs VALUES (1, 'img_dir', 'images', 1, 'admin', 'text_input', 0)";
$create_sql[] = "INSERT INTO ".PFX."txp_prefs VALUES (1, 'comments_disallow_images', '0', 0, 'comments', 'yesnoradio', 170)";
$create_sql[] = "INSERT INTO ".PFX."txp_prefs VALUES (1, 'comments_sendmail', '0', 0, 'comments', 'yesnoradio', 160)";
$create_sql[] = "INSERT INTO ".PFX."txp_prefs VALUES (1, 'file_max_upload_size', '2000000', 1, 'admin', 'text_input', 0)";
$create_sql[] = "INSERT INTO ".PFX."txp_prefs VALUES (1, 'file_list_pageby', '25', 2, 'publish', 'text_input', 0)";
$create_sql[] = "INSERT INTO ".PFX."txp_prefs VALUES (1, 'path_to_site', '', 2, 'publish', 'text_input', 0)";
$create_sql[] = "INSERT INTO ".PFX."txp_prefs VALUES (1, 'article_list_pageby', '25', 2, 'publish', 'text_input', 0)";
$create_sql[] = "INSERT INTO ".PFX."txp_prefs VALUES (1, 'link_list_pageby', '25', 2, 'publish', 'text_input', 0)";
$create_sql[] = "INSERT INTO ".PFX."txp_prefs VALUES (1, 'image_list_pageby', '25', 2, 'publish', 'text_input', 0)";
$create_sql[] = "INSERT INTO ".PFX."txp_prefs VALUES (1, 'log_list_pageby', '25', 2, 'publish', 'text_input', 0)";
$create_sql[] = "INSERT INTO ".PFX."txp_prefs VALUES (1, 'comment_list_pageby', '25', 2, 'publish', 'text_input', 0)";
$create_sql[] = "INSERT INTO ".PFX."txp_prefs VALUES (1, 'permlink_mode', '".addslashes($permlink_mode)."', 0, 'publish', 'permlinkmodes', 90)";
$create_sql[] = "INSERT INTO ".PFX."txp_prefs VALUES (1, 'comments_are_ol', '1', 0, 'comments', 'yesnoradio', 150)";
$create_sql[] = "INSERT INTO ".PFX."txp_prefs VALUES (1, 'is_dst', '0', 0, 'publish', 'yesnoradio', 60)";
$create_sql[] = "INSERT INTO ".PFX."txp_prefs VALUES (1, 'locale', 'en_GB.UTF-8', 2, 'publish', 'text_input', 0)";
$create_sql[] = "INSERT INTO ".PFX."txp_prefs VALUES (1, 'tempdir', '".addslashes(find_temp_dir())."', 1, 'admin', 'text_input', 0)";
$create_sql[] = "INSERT INTO ".PFX."txp_prefs VALUES (1, 'file_base_path', '".addslashes(dirname(txpath).DS.'files')."', 1, 'admin', 'text_input', 0)";
$create_sql[] = "INSERT INTO ".PFX."txp_prefs VALUES (1, 'blog_uid', '". $prefs['blog_uid'] ."', 2, 'publish', 'text_input', 0)";
$create_sql[] = "INSERT INTO ".PFX."txp_prefs VALUES (1, 'blog_mail_uid', '".addslashes($_POST['email'])."', 2, 'publish', 'text_input', 0)";
$create_sql[] = "INSERT INTO ".PFX."txp_prefs VALUES (1, 'blog_time_uid', '2005', 2, 'publish', 'text_input', 0)";
$create_sql[] = "INSERT INTO ".PFX."txp_prefs VALUES (1, 'edit_raw_css_by_default', '1', 1, 'css', 'yesnoradio', 0)";
$create_sql[] = "INSERT INTO ".PFX."txp_prefs VALUES (1, 'allow_page_php_scripting', '1', 1, 'publish', 'yesnoradio', 0)";
$create_sql[] = "INSERT INTO ".PFX."txp_prefs VALUES (1, 'allow_article_php_scripting', '1', 1, 'publish', 'yesnoradio', 0)";
$create_sql[] = "INSERT INTO ".PFX."txp_prefs VALUES (1, 'allow_raw_php_scripting', '0', 1, 'publish', 'yesnoradio', 0)";
$create_sql[] = "INSERT INTO ".PFX."txp_prefs VALUES (1, 'textile_links', '0', 1, 'link', 'yesnoradio', 0)";
$create_sql[] = "INSERT INTO ".PFX."txp_prefs VALUES (1, 'show_article_category_count', '1', 2, 'category', 'yesnoradio', 0)";
$create_sql[] = "INSERT INTO ".PFX."txp_prefs VALUES (1, 'show_comment_count_in_feed', '1', 1, 'publish', 'yesnoradio', 0)";
$create_sql[] = "INSERT INTO ".PFX."txp_prefs VALUES (1, 'syndicate_body_or_excerpt', '1', 1, 'publish', 'yesnoradio', 0)";
$create_sql[] = "INSERT INTO ".PFX."txp_prefs VALUES (1, 'include_email_atom', '1', 1, 'publish', 'yesnoradio', 0)";
$create_sql[] = "INSERT INTO ".PFX."txp_prefs VALUES (1, 'comment_means_site_updated', '1', 1, 'publish', 'yesnoradio', 0)";
$create_sql[] = "INSERT INTO ".PFX."txp_prefs VALUES (1, 'never_display_email', '0', 1, 'publish', 'yesnoradio', 0)";
$create_sql[] = "INSERT INTO ".PFX."txp_prefs VALUES (1, 'comments_require_name', '1', 1, 'comments', 'yesnoradio', 0)";
$create_sql[] = "INSERT INTO ".PFX."txp_prefs VALUES (1, 'comments_require_email', '1', 1, 'comments', 'yesnoradio', 0)";
$create_sql[] = "INSERT INTO ".PFX."txp_prefs VALUES (1, 'articles_use_excerpts', '1', 1, 'publish', 'yesnoradio', 0)";
$create_sql[] = "INSERT INTO ".PFX."txp_prefs VALUES (1, 'allow_form_override', '1', 1, 'publish', 'yesnoradio', 0)";
$create_sql[] = "INSERT INTO ".PFX."txp_prefs VALUES (1, 'attach_titles_to_permalinks', '1', 1, 'publish', 'yesnoradio', 0)";
$create_sql[] = "INSERT INTO ".PFX."txp_prefs VALUES (1, 'permalink_title_format', '1', 1, 'publish', 'yesnoradio', 0)";
$create_sql[] = "INSERT INTO ".PFX."txp_prefs VALUES (1, 'expire_logs_after', '7', 1, 'publish', 'text_input', 0)";
$create_sql[] = "INSERT INTO ".PFX."txp_prefs VALUES (1, 'use_plugins', '1', 1, 'publish', 'yesnoradio', 0)";
$create_sql[] = "INSERT INTO ".PFX."txp_prefs VALUES (1, 'custom_1_set', 'custom1', 1, 'custom', 'text_input', 1)";
$create_sql[] = "INSERT INTO ".PFX."txp_prefs VALUES (1, 'custom_2_set', 'custom2', 1, 'custom', 'text_input', 2)";
$create_sql[] = "INSERT INTO ".PFX."txp_prefs VALUES (1, 'custom_3_set', '', 1, 'custom', 'text_input', 3)";
$create_sql[] = "INSERT INTO ".PFX."txp_prefs VALUES (1, 'custom_4_set', '', 1, 'custom', 'text_input', 4)";
$create_sql[] = "INSERT INTO ".PFX."txp_prefs VALUES (1, 'custom_5_set', '', 1, 'custom', 'text_input', 5)";
$create_sql[] = "INSERT INTO ".PFX."txp_prefs VALUES (1, 'custom_6_set', '', 1, 'custom', 'text_input', 6)";
$create_sql[] = "INSERT INTO ".PFX."txp_prefs VALUES (1, 'custom_7_set', '', 1, 'custom', 'text_input', 7)";
$create_sql[] = "INSERT INTO ".PFX."txp_prefs VALUES (1, 'custom_8_set', '', 1, 'custom', 'text_input', 8)";
$create_sql[] = "INSERT INTO ".PFX."txp_prefs VALUES (1, 'custom_9_set', '', 1, 'custom', 'text_input', 9)";
$create_sql[] = "INSERT INTO ".PFX."txp_prefs VALUES (1, 'custom_10_set', '', 1, 'custom', 'text_input', 10)";
$create_sql[] = "INSERT INTO ".PFX."txp_prefs VALUES (1, 'ping_textpattern_com', '1', 1, 'publish', 'yesnoradio', 0)";
$create_sql[] = "INSERT INTO ".PFX."txp_prefs VALUES (1, 'use_dns', '1', 1, 'publish', 'yesnoradio', 0)";
$create_sql[] = "INSERT INTO ".PFX."txp_prefs VALUES (1, 'admin_side_plugins', '1', 1, 'publish', 'yesnoradio', 0)";
$create_sql[] = "INSERT INTO ".PFX."txp_prefs VALUES (1, 'comment_nofollow', '1', 1, 'publish', 'yesnoradio', 0)";
$create_sql[] = "INSERT INTO ".PFX."txp_prefs VALUES (1, 'use_mail_on_feeds_id', '0', 1, 'publish', 'yesnoradio', 0)";
$create_sql[] = "INSERT INTO ".PFX."txp_prefs VALUES (1, 'max_url_len', '200', 1, 'publish', 'text_input', 0)";
$create_sql[] = "INSERT INTO ".PFX."txp_prefs VALUES (1, 'spam_blacklists', 'sbl.spamhaus.org', 1, 'publish', 'text_input', 0)";
$create_sql[] = "INSERT INTO ".PFX."txp_prefs VALUES (1, 'override_emailcharset', '0', 1, 'admin', 'yesnoradio', 21)";
$create_sql[] = "INSERT INTO ".PFX."txp_prefs VALUES (1, 'production_status', 'testing', 0, 'publish', 'prod_levels', 210)";
$create_sql[] = "INSERT INTO ".PFX."txp_prefs VALUES (1, 'comments_auto_append', '1', 0, 'comments', 'yesnoradio', 211)";
$create_sql[] = "INSERT INTO ".PFX."txp_prefs VALUES (1, 'dbupdatetime', '1122194504', 2, 'publish', 'text_input', 0)";
$create_sql[] = "INSERT INTO ".PFX."txp_prefs VALUES (1, 'version', '1.0rc4', 2, 'publish', 'text_input', 0)";

$create_sql[] = "CREATE TABLE ".PFX."txp_section (
  name varchar(128) NOT NULL default '',
  page varchar(128) NOT NULL default '',
  css varchar(128) NOT NULL default '',
  is_default smallint NOT NULL default '0',
  in_rss smallint NOT NULL default '1',
  on_frontpage smallint NOT NULL default '1',
  searchable smallint NOT NULL default '1',
  title varchar(255) NOT NULL default '',
  PRIMARY KEY (name)
) $tabletype";

$create_sql[] = "INSERT INTO ".PFX."txp_section VALUES ('article', 'archive', 'default', 1, 1, 1, 1, 'Article')";
$create_sql[] = "INSERT INTO ".PFX."txp_section VALUES ('default', 'default', 'default', 0, 1, 1, 1, 'default')";
$create_sql[] = "INSERT INTO ".PFX."txp_section VALUES ('about', 'default', 'default', 0, 0, 0, 1, 'About')";

$create_sql[] = "CREATE TABLE ".PFX."txp_users (
  user_id $autoinc,
  name varchar(64) NOT NULL default '',
  pass varchar(128) NOT NULL default '',
  RealName varchar(64) NOT NULL default '',
  email varchar(100) NOT NULL default '',
  privs smallint NOT NULL default '1',
  last_access $datetime NOT NULL default '$zerodatetime',
  nonce varchar(64) NOT NULL default '',
  PRIMARY KEY  (user_id)
) $tabletype";

$create_sql[] = 'CREATE UNIQUE INDEX '.PFX.'user_name ON '.PFX.'txp_users (name)';

if (MDB_TYPE == 'pg') {
	# mimic some mysql-specific functions in postgres
	$DB->query("create function unix_timestamp(timestamp) returns integer as 'select date_part(''epoch'', $1)::int4 as result' language 'sql';");
	$DB->query("create function from_unixtime(integer) returns abstime as 'select abstime($1) as result' language 'sql';");
	$DB->query("create function password(text) returns text as 'select md5($1) as result' language 'sql';");
	$DB->query("create function old_password(text) returns text as 'select md5($1) as result' language 'sql';");
}elseif (MDB_TYPE == 'pdo_sqlite'){

}

$GLOBALS['txp_install_successful'] = true;
$GLOBALS['txp_err_count'] = 0;
foreach ($create_sql as $query)
{
	$result = $DB->query($query);
	if (!$result) 
	{
		$GLOBALS['txp_err_count']++;
		echo "<b>".$GLOBALS['txp_err_count'].".</b> ".$DB->lasterror()."<br />\n";
		echo "<!--\n $query \n-->\n";
		$GLOBALS['txp_install_successful'] = false;
	}
}

# Skip the RPC language fetch when testing
if (defined('TXP_TEST'))
	return;

require_once txpath.'/lib/IXRClass.php';
$client = new IXR_Client('http://rpc.textpattern.com');
if (!$client->query('tups.getLanguage',$prefs['blog_uid'],$lang))
{
	# If cannot install from lang file, setup the english lang
	if (!install_language_from_file($lang))
	{
		$lang = 'en-gb';
		include_once txpath.'/setup/en-gb.php';
		if (!@$lastmod) $lastmod = $zerodatetime;
		foreach ($en_gb_lang as $evt_name => $evt_strings)
		{
			foreach ($evt_strings as $lang_key => $lang_val)
			{
				$lang_val = addslashes($lang_val);
				if (@$lang_val)
					$DB->query("INSERT INTO ".PFX."txp_lang (lang,name,event,data,lastmod) VALUES ('en-gb','$lang_key','$evt_name','$lang_val','$lastmod')");
			}
		}
	}
}else {
	$response = $client->getResponse();
	$lang_struct = unserialize($response);
	if (MDB_TYPE == 'pdo_sqlite') {
		
		$stmt = $DB->prepare("INSERT INTO ".PFX."txp_lang (lang,name,event,data,lastmod) VALUES ('$lang', ?, ?, ?, ?)");
		foreach ($lang_struct as $item){
			$stmt->execute(array_values($item));
		}
	}else{
		foreach ($lang_struct as $item)
		{
			foreach ($item as $name => $value) 
				$item[$name] = addslashes($value);
			$DB->query("INSERT INTO ".PFX."txp_lang (lang,name,event,data,lastmod) VALUES ('$lang','$item[name]','$item[event]','$item[data]','".strftime('%Y-%m-%d %H:%M:%S',$item['uLastmod'])."')");
		}
	}		
}

?>
