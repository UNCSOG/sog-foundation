<?php
$links_manager = new OTGS\Toolset\CRED\Controller\LinksManager();
$doc_link_args = array(
    'utm_source' => 'plugin',
    'utm_campaign' => 'forms',
    'utm_medium' => 'gui',
    'utm_term' => 'forms-creating-doc'
);
?>
<div class="howtodisplaybox" id="howtodisplay">
    <div id="minor-publishing">
        Read the documentation to learn how to <a href="<?php echo $links_manager->get_escaped_link( CRED_DOC_LINK_FRONTEND_CREATING_CONTENT, $doc_link_args ); ?>">display Toolset forms</a>.
        <div class="clear"></div>
    </div>
</div>
