<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly.?>
<h1>Alert Service</h1>
<?php //error_log( var_export( $data->status, true ) ) ?>
<div class="alert-service-data-container">
      <pre class='alert'>
            <?php var_export($data->alert) ?>
      </pre>

      <table class="form-table" role="presentation">
            <tbody>
                  <tr>
                        <th scope="row"><label for="site_name">Current Alert Status:</label></th>
                        <td class="status">
                              <?php echo $data->status ?>
                        </td>
                  </tr>
            </tbody>
      </table>


      <a id="publish-alert-button"  class="button button-primary button-large" href="<?php echo $data->publish_url ?>"<?php echo $data->status=='published' ? ' disabled' : '' ?> >Publish Alert To Sites</a>
      <a id="unpublish-alert-button"  class="button button-primary button-large" href="<?php echo $data->unpublish_url ?>"<?php echo $data->status=='unpublished' ? ' disabled' : '' ?> >Unpublish Alert from Sites</a>
</div>