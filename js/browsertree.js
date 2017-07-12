/* Settings for dynatree */
jQuery(document).ready( function($) {
    
    // Get data from matchmail_dynatree_data function PHP
    var obj = $.parseJSON(hypernews_dynatree_data);

    $("#hypernews_browse_tree").dynatree({

        //Tree parameters
        persist: false,
        checkbox: true,
        selectMode: 1,
        activeVisible: true,
        children: obj,
        debugLevel: 1,
        onClick: function(node, event) {
            $("#hypernews_browse_selected").val(node.data.key);
        }
    });

});