<?php

namespace Wpae\AddonAPI;

class PMXE_Addon_Resolver {

    public PMXE_Addon_Base $addon;
    public $record;
    public $recordId;

    public function __construct( PMXE_Addon_Base $addon, $object, $recordId ) {
        $this->addon    = $addon;
        $this->record   = $object;
        $this->recordId = $recordId;
    }

    public function getCast( $type ) {
        return $this->addon->casts[ $type ] ?? null;
    }

    public function resolveFieldValue( PMXE_Addon_Field $field ) {
        $value = $this->addon->resolveFieldValue( $field, $this->record, $this->recordId );

        return $this->processValue( $field, $value );
    }

    public function processValue( PMXE_Addon_Field $field, $value ) {
        $cast = $this->getCast( $field->type );

        return $cast ? ( new $cast )( $field, $value ) : $value;
    }
}
