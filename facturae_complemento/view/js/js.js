
function getUrlVars(){
  var vars = [], hash;
  var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');
  for(var i = 0; i < hashes.length; i++){
    hash = hashes[i].split('=');
    vars.push(hash[0]);
    vars[hash[0]] = hash[1];
  }
  return vars;
}

function add_select(name){
  $("input[name='"+ name +"']").css('display','none');
  $("input[name='"+ name +"']").parent('#'+ name +'_add').append('<select id="sel_'+ name +'" class="form-control"></select>');
  $("#sel_" + name).append('<option value="">Selecciona</option>');
  $("#sel_" + name).append('<option value="reset">&#9668; Volver...</i></option>');
  $('#crea_sel_'+ name).css('display','none');
  $.ajax({
    type: "GET",
    url: "index.php?page=codigos_json&id=" + getUrlVars()['id'],
    success: function(success){
      if(success.result){
        //console.log(success.result);
        var resultado = success.result;
        for (var i in resultado){
          if(name === resultado[i].tipo){
            $("#sel_"+ resultado[i].tipo).append('<option value="'+ resultado[i].codigo +'">&#9658; '+ resultado[i].nombre +'</option>');
          }
        }
      }
    }
  });
}

function adapta(obj){
  $("input[name='"+obj+"']").parent('.form-group').append('<div class="input-group" id="'+ obj +'_add"></div>');
  $("#"+ obj +"_add").append($("input[name='"+ obj +"']"));
  $("#"+ obj +"_add").append('<span id="crea_sel_'+ obj +'" class="input-group-addon"><i class="fa fa-th-list"></i></span>');
  $('#crea_sel_'+ obj).attr('data-content','Click para ver selector');
  $('#crea_sel_'+ obj).attr('data-toggle','popover');
  $('#crea_sel_'+ obj).attr('data-trigger','hover');
  $('#crea_sel_'+ obj).attr('data-placement','top');
  $("#crea_sel_"+ obj).attr("onclick","add_select('"+ obj +"');");
  $("#crea_sel_"+ obj).click(function(){
    $("#"+ obj +"_add").append('<span id="reset_sel_'+ obj +'" class="input-group-addon"><i class="fa fa-th-list"></i></span>');
    $('#sel_'+ obj).change(function(){
      if($(this).val() == 'reset'){
        $('#crea_sel_'+ obj).css('display','');
        $("input[name='"+ obj +"']").css('display','');
        $('#reset_sel_'+ obj).remove();
        $(this).remove('#sel_'+ obj);
      }else{
        $("input[name='"+obj+"']").val($(this).val());
      }
    });
  });
}

$( document ).ready(function() {

  adapta('codoficina');
  adapta('codorgano');
  adapta('codunidad');
  adapta('codorganop');
  $('[data-toggle="popover"]').popover();

});
