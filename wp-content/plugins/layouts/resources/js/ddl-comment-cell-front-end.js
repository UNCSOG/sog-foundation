var DDLayout = DDLayout || {};

DDLayout.DDL_CommentsFrontend = function($){
    var self = this, loading_gif = new WPV_Toolset.Utils.Loader();

    self.init = function(){
        self.prev_comments();
        self.next_comments();
    };

    self.next_comments = function(){
        jQuery(document).on('click', '.js-ddl-next-link a', function (event) {
            event.preventDefault();
            loading_gif.loadShow($(this), true);
            var data = {
                action: 'ddl_load_comments_page_content',
                page: jQuery(this).data('page'),
                postid: jQuery(this).data('postid'),
                layout_name: DDL_Comments_cell.layout_name,
                cell_id:DDL_Comments_cell.cell_id,
                wpnonce: DDL_Comments_cell.security
            };
     //       console.log(data);
            $.post(DDL_Comments_cell.ajaxurl, data, function (response) {
                if ((typeof(response) !== 'undefined')) {
                    jQuery('#comments').html(response);
                    loading_gif.loadHide();
                }
            })
                .fail(function (jqXHR, textStatus, errorThrown) {
                    console.log("Error: ", textStatus, errorThrown);
                });

            return false;
        });
    };

    self.prev_comments = function(){
        jQuery(document).on('click', '.js-ddl-previous-link a', function (event) {
            event.preventDefault();
            loading_gif.loadShow($(this), true);

            var data = {
                action: 'ddl_load_comments_page_content',
                page: jQuery(this).data('page'),
                postid: jQuery(this).data('postid'),
                layout_name: DDL_Comments_cell.layout_name,
                cell_id:DDL_Comments_cell.cell_id,
                wpnonce: DDL_Comments_cell.security
            };
  //          console.log(data);
            $.post(DDL_Comments_cell.ajaxurl, data, function (response) {
                if ((typeof(response) !== 'undefined')) {
                    jQuery('#comments').html(response);
                    loading_gif.loadHide();
                }
            })
                .fail(function (jqXHR, textStatus, errorThrown) {
                    console.log("Error: ", textStatus, errorThrown);
                });

            return false;
        });
    };

    self.init();
};

(function ($) {
    jQuery(function ($) {
        new DDLayout.DDL_CommentsFrontend($);
    });
}(jQuery))
