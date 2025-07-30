DDLayout.models.cells.Parent = DDLayout.models.abstract.Element.extend({
    defaults:{
        type:''
        , name:''
        , cssframework:''
        , template:''
        , parent:0
        , Rows:DDLayout.models.collections.Rows
        , width:12
        , cssClass:'span12'
        , id: 0
        , kind: 'Layout'
        , has_child: false
        , slug: ''
        , children_to_delete : null
        , child_delete_mode : null
        , has_loop:false
        , has_post_content_cell: false
    }
});