var Ddl_Menu_Cell_Front_End = Ddl_Menu_Cell_Front_End || {};

Ddl_Menu_Cell_Front_End.Main = function(){
        var self = this;

        self.init = function(){
            self.fix_link_behaviour();
            self.add_class_open();
            self.fix_wpml_link();
        };

        self.fix_link_behaviour = function(){
            jQuery( ".ddl-dropdown-submenu-layouts" ).on('click', function(event) {
                var $this = jQuery(this);

                if( jQuery( this ).parent().find(".ddl-dropdown-submenu-layouts").hasClass('open') ) {
                    jQuery( this ).parent().find(".ddl-dropdown-submenu-layouts").removeClass('open');
                    jQuery( this ).find(".ddl-dropdown-submenu-layouts").removeClass('open');
                } else {
                    jQuery( this ).parent().find(".ddl-dropdown-submenu-layouts").removeClass('open');
                    jQuery( this ).find(".ddl-dropdown-submenu-layouts").removeClass('open');
                    if ( jQuery(window).width() > 768 ){
                        var current_offset = $this.offset();
                        var left_offset = $this.width();
                        if ( ($this.offset().left+$this.find("ul").width()+120) > jQuery(window).width() ){
                            left_offset = '-'+$this.find("ul").width();
                        }
                        $this.find("ul").css({ 'left':left_offset+'px', top:($this.height()/4)+'px'});
                    }

                    event.stopPropagation();
                    jQuery( this ).find(".ddl-dropdown-submenu-layouts").removeClass('open');
                    jQuery( this ).parents(".ddl-dropdown-submenu-layouts").addClass('open');
                    jQuery( this ).toggleClass('open');
                }

                return jQuery(event.target).closest('li').find('ul').length === 0;
            });
        };

        self.add_class_open = function(){
            jQuery( ".ddl-dropdown" ).click(function(event) {
                jQuery( this ).find(".ddl-dropdown-submenu-layouts").removeClass('open');
            });
        };

        self.fix_wpml_link = function(){
            var $wpml_menu = jQuery('.ddl-nav-wrap').find('.menu-item-language-current').eq(0),
                open = false;

            if( $wpml_menu.length === 0 ) return;

            jQuery('.ddl-nav-wrap .menu-item-language').show();

            var $a = $wpml_menu.find('a').eq(0), $ul = $wpml_menu.find('ul').eq(0);

            if( $a.length ) {
                $wpml_menu.addClass('menu-item-has-children ddl-dropdown');
                jQuery.data( $a[0], 'toggle', 'dropdown' );
                jQuery.data( $a[0], 'target', '#' );
                jQuery.data( $a[0], 'open', false );
                var caret = jQuery('<b class="caret"></b>');
                $a.append(caret);
                caret.css('margin-left', '4px');
                $a.removeAttr('onclick');
                $a.addClass('ddl-dropdown-toggle');
                $ul.addClass('ddl-dropdown-menu');
                $a.on('click', function(event){
                        if( $a.data('open') == false ){
                            jQuery.data( this, 'open', true );
                            $ul.show();
                        } else {
                            jQuery.data( this, 'open', false );
                            $ul.hide();
                        }
                });

                jQuery(document).on('click', function(event){
                        if(
                            event.target === $a[0] ||
                            event.target === $wpml_menu[0]
                        ){
                            return false;
                        } else{
                            $ul.hide();
                            jQuery.data( $a[0], 'open', false );
                        }
                });
            }
        };


    self.init();
};


jQuery(function(){
    Ddl_Menu_Cell_Front_End.main = new Ddl_Menu_Cell_Front_End.Main();
});