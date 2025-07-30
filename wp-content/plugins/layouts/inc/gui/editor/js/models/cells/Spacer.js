/**
 * Created with JetBrains PhpStorm.
 * User: rogopag
 * Date: 9/17/13
 * Time: 2:03 PM
 * To change this template use File | Settings | File Templates.
 */
DDLayout.models.cells.Spacer = DDLayout.models.abstract.Element.extend({
    defaults:{
        kind:'Spacer'
    },
    compound: '',
    isEmpty:function()
    {
        return true;
    }
});