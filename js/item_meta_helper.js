jQuery(document).ready(function(){
  function assoc_checked(input, klass){
    if (jQuery(input).is(":checked")){
      jQuery(klass).css("display","table-row");
    } else {
      jQuery(klass).css("display","none");
    }
  }

  assoc_checked("input[name='digidiss_assoc']", ".assoc");
  assoc_checked("input[name='digidiss_niec']", ".niec");
  assoc_checked("input[name='digidiss_appears']", ".appears");
  assoc_checked("input[name='digidiss_mirador']", ".mirador");

  jQuery("input[name='digidiss_assoc']").on('change', function(){
    assoc_checked("input[name='digidiss_assoc']", ".assoc");
  });
  jQuery("input[name='digidiss_niec']").on('change', function(){
    assoc_checked("input[name='digidiss_niec']", ".niec");
  });
  jQuery("input[name='digidiss_appears']").on('change', function(){
    assoc_checked("input[name='digidiss_appears']", ".appears");
  });
  jQuery("input[name='digidiss_mirador']").on('change', function(){
    assoc_checked("input[name='digidiss_mirador']", ".mirador");
  });

  jQuery("input[name='digidiss_facets[]'], input[name='digidiss_niec_metadata[]']").on('change', function(){
    if (jQuery(this).is(":checked")){
      jQuery(this).parents("td").next(".title").css("display","table-cell");
    } else {
      jQuery(this).parents("td").next(".title").css("display","none");
    }
  });

  jQuery("#facets_sortable").sortable();
  jQuery("#niec_facets_sortable").sortable();
  jQuery("#item_metadata_sortable").sortable();
  jQuery(".add-item-meta").on("click", function(e){
    e.preventDefault();
    jQuery("#item_metadata_sortable").append('<tr class="ui-sortable-handle"><td style="padding:0"><label><input type="checkbox" name="digidiss_item_page_metadata[]"> <span class="dashicons dashicons-move"></span> <input type="text" /></label></td></tr>');
    jQuery("#item_metadata_sortable").sortable({ refresh: item_metadata_sortable });
    update_values();
  });

  function update_values(){
    jQuery("#item_metadata_sortable input[type='text']").on("change", function(e){
      val = jQuery(this).val();
      jQuery(this).siblings('input[type="checkbox"]').val(val);
      if (val != ""){
        jQuery(this).siblings('input[type="checkbox"]').prop("checked", true);
      } else {
        jQuery(this).siblings('input[type="checkbox"]').prop("checked", false);
      }
    });
  }

});
