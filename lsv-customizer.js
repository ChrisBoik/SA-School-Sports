jQuery(function($) {
  const cont = $('.lsv-repeater-container');
  const tmpl = $('template.lsv-item-template').html();
  const hidden = $('#customize-control-lsv_data input[type=hidden]');

  function load() {
    cont.empty();
    let data = JSON.parse(hidden.val() || '[]');
    data.forEach(item => add(item));
  }

  function save() {
    let arr = [];
    cont.find('.lsv-item').each(function() {
      let $f = $(this);
      arr.push({
        name: $f.find('input[name="name"]').val(),
        image_url: $f.find('input[name="image_url"]').val(),
        fallback: $f.find('input[name="fallback"]').val(),
        css_class: $f.find('input[name="css_class"]').val(),
        apply_in: $f.find('input[name="apply_in[]"]:checked').map((_,c)=>c.value).get()
      });
    });
    hidden.val(JSON.stringify(arr)).trigger('change');
  }

  function add(item={name:'',image_url:'',fallback:'',css_class:'',apply_in:[]}) {
    let $itm = $(tmpl);
    $itm.find('input[name="name"]').val(item.name);
    $itm.find('input[name="image_url"]').val(item.image_url);
    $itm.find('.preview').attr('src',item.image_url);
    $itm.find('input[name="fallback"]').val(item.fallback);
    $itm.find('input[name="css_class"]').val(item.css_class);
    item.apply_in.forEach(val => $itm.find(`input[name="apply_in[]"][value="${val}"]`).prop('checked',true));

    $itm.find('.upload').on('click', function(e){
      e.preventDefault();
      let frame = wp.media({title:'Select Logo', multiple: false, library:{type:'image'}});
      frame.on('select', function(){
        let att = frame.state().get('selection').first().toJSON();
        $itm.find('input[name="image_url"]').val(att.url);
        $itm.find('.preview').attr('src', att.url);
        save();
      });
      frame.open();
    });
    $itm.find('input,checkbox').on('change', save);
    $itm.find('.lsv-remove-item').on('click', function() {
      $itm.remove(); save();
    });
    cont.append($itm);
  }

  $('.lsv-add-item').on('click', function() { add(); save(); });
  load();
});
