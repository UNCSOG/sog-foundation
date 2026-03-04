<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly.?>
<script type="text/html" id="tmpl-alert-service-admin-modal">

<div id="{{ data.id }}" class="{{data.classes}}" >
  <button class="close-button" data-lity-close>Close</button>
  <div id="{{ data.id }}-content">
    {{{ data.content }}}
  </div>
</div>

</script>