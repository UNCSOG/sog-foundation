<?php

namespace Wpae\AddonAPI;

class PMXE_Addon_View {
    public PMXE_Addon_Base $addon;

    public function __construct( PMXE_Addon_Base $addon ) {
        $this->addon = $addon;
    }

    private function renderView( $viewName, $extraData = [] ) {
        $groups = $this->addon->getGroupsByExportType();
        $groups = array_map( function ( $group ) {
            $group['fields'] = $this->addon->getFieldsByGroup( $group['id'] );

            return $group;
        }, $groups );

        if ( empty( $groups ) ) {
            return;
        }

        $data = array_merge( [
            'groups' => $groups,
            'addon'  => $this->addon,
        ], $extraData );

        view( $viewName, $data );
    }

    public function render( $i ) {
        $this->renderView( 'fields', [ 'i' => $i ] );
    }

    public function filters() {
        $this->renderView( 'filters' );
    }

    public function newField() {
        $this->renderView( 'new-field' );
    }
}
