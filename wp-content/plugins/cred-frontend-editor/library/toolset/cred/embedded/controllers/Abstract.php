<?php
abstract class CRED_Abstract_Controller
{
    protected function redirectTo($url)
    {
        //header("location: $url");
        wp_redirect($url);
    }
    
    protected function renderJsFunction(array $func_data=array())
    {
        ob_start();
        ?>
        <script type='text/javascript'>
        /*<![CDATA[ */
            <?php foreach ($func_data as $func=>$args) 
            {
                echo $func.'('.implode(',',$args).');';
            }
            ?>
        /*]]>*/
        </script>
        <?php
        return ob_get_clean();
    }
}
