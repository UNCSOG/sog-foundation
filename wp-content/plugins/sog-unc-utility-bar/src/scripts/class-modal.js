import lity from "lity";


export default class AdminModal{

      
      constructor(options) {

      }
      
      make_a_modal($id = "modal", $classes = "notice-success", $msg = "hello world") {
            var modal = wp.template();
            var $object = modal({
                  id: $id,
                  classes: $classes,
                  content: $msg,
            });
      
            lity($object);
      }
} 

