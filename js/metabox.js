/*
 * Tree
 */
jQuery(document).ready( function($) {
        
        var obj = $.parseJSON(matchmail_dynatree_data);


        $("#mm_editor_tree").dynatree({
            //Tree parameters
            persist: true,
            checkbox: true,
            selectMode: 3,
            activeVisible: true,
            children: obj,
            onSelect: function(select, node) {
                // Get a list of all selected nodes, and convert to a key array:
                var selKeys = $.map(node.tree.getSelectedNodes(), function(node){
                  return node.data.key;
                });
                $("#mm_metabox_value").val(selKeys.join(","));
                // Get a list of all selected TOP nodes
                var selRootNodes = node.tree.getSelectedNodes(true);
                // ... and convert to a key array:
                var selRootKeys = $.map(selRootNodes, function(node){
                  return node.data.key;
                });
            },
            onDblClick: function(node, event) {
                node.toggleSelect();
            },
            onKeydown: function(node, event) {
                if( event.which == 32 ) {
                  node.toggleSelect();
                  return false;
                  }
            },
            onPostInit: function(isReloading, isError) {

                var keystring = $("#mm_metabox_value").val();
                var keys = new Array();
                
                if (keystring!="" && keystring != "0"){
                    keys = keystring.split(','); 
                }
                   
                //Unselect nodes and set selected!
                var tree = this;
                var nodes = tree.getSelectedNodes();
                $(nodes).each(function(index) {
                    tree.selectKey(this.data.key, false);
                });

                $(keys).each(function(index) {
                    tree.selectKey(this, true);
                });
            }
        });


        
        
});

