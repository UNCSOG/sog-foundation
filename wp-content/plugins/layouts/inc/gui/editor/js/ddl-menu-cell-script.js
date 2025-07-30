var DDLayout = DDLayout || {};
DDLayout.MenuCell = function($){
    
    var self = this;
    
    jQuery(document).on('menu-cell.dialog-open', function(e, content, dialog) {
        self.init();
    });
    
    
    self.init = function(){
        var current_value = $( "select[name='ddl-layout-menu_dir']" ).val();
        self.show_hide_selector(current_value);

        $( "select[name='ddl-layout-menu_dir']" ).change(function() {

            var selected_menu_type = $(this).val();
            self.show_hide_selector(selected_menu_type);
        });
    };
    
    self.show_hide_selector = function(selected_menu_type){
        if(selected_menu_type === 'nav-horizontal'){
            $("select[name='ddl-layout-menu_alignment']").parent('p').fadeIn(500);
        } else {
            $("select[name='ddl-layout-menu_alignment']").parent('p').hide();
        }
    };
    
    
    
};


(function($){
     $(function(){
        DDLayout.menu_cell =  new DDLayout.MenuCell($);
     });
}(jQuery));

