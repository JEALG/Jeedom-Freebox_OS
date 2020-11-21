$("#table_cmd").sortable({
	axis: "y",
	cursor: "move",
	items: ".cmd",
	placeholder: "ui-state-highlight",
	tolerance: "intersect",
	forcePlaceholderSize: true
});

$('#bt_resetSearch').off('click').on('click', function () {
	$('#in_searchEqlogic').val('')
	$('#in_searchEqlogic').keyup();
})

$('.cmdAction[data-action=add]').on('click', function() {
	addCmdToTable()
	$('.cmd:last .cmdAttr[data-l1key=type]').trigger('change')
	modifyWithoutSave = true
  })

$('body').off('Freebox_OS::camera').on('Freebox_OS::camera', function (_event, _options) {
	var camera = jQuery.parseJSON(_options);
	bootbox.confirm("{{Une caméra Freebox a été détectée (<b>" + camera.name + "</b>)<br>Voulez-vous l’ajouter au Plugin Caméra ?}}", function (result) {
		if (result) {
			$.ajax({
				type: 'POST',
				url: 'plugins/Freebox_OS/core/ajax/Freebox_OS.ajax.php',
				data: {
					action: 'createCamera',
					name: camera.name,
					id: camera.id,
					room: camera.room,
					url: camera.url
				},
				dataType: 'json',
				global: true,
				error: function (request, status, error) {},
				success: function (data) {
					if (data.state != 'ok') {
						$('#div_alert').showAlert({
							message: data.result,
							level: 'danger'
						});
						return;
					}
					$('#div_alert').showAlert({
						message: "{{La caméra (<b>" + camera.name + "</b>) a été ajoutée avec succès}}",
						level: 'success'
					});
					window.location.reload();
				}
			});
		}
	});

});

$('.authentification').on('click', function () {
	$('#md_modal').dialog({
		title: "{{Authentification Freebox}}",
		height: 700,
		width: 850
	});
	$('#md_modal').load('index.php?v=d&modal=authentification&plugin=Freebox_OS&type=Freebox_OS').dialog('open');
});

$('.health').on('click', function () {
	$('#md_modal').dialog({
		title: "{{Santé Freebox}}",
		height: 700,
		width: 850
	});
	$('#md_modal').load('index.php?v=d&modal=health&plugin=Freebox_OS&type=Freebox_OS').dialog('open');
});

$('.eqLogicAction[data-action=eqlogic_standard]').on('click', function () {
	$('#div_alert').showAlert({
		message: '{{Recherche des <b>Equipements standards</b>}}',
		level: 'warning'
	});
	$.ajax({
		type: 'POST',
		async: true,
		url: 'plugins/Freebox_OS/core/ajax/Freebox_OS.ajax.php',
		data: {
			action: 'SearchArchi'
		},
		dataType: 'json',
		global: false,
		error: function (request, status, error) {
			$('#div_alert').showAlert({
				message: '{{Erreur recherche des <b>Equipements standards</b>}}',
				level: 'danger'
			});
		},
		success: function (data) {
			$('#div_alert').showAlert({
				message: "{{Opération réalisée avec succès. Appuyez sur Ctrl + F5 (sur Mac CMD + R) si votre écran ne s'est pas actualisé}}",
				level: 'success'

			});
			window.location.reload();
		}
	});

});

$('.eqLogicAction[data-action=control_parental]').on('click', function () {
	$('#div_alert').showAlert({
		message: '{{Recherche <b>Contrôle Parental</b>}}',
		level: 'warning'
	});
	$.ajax({
		type: 'POST',
		async: true,
		url: 'plugins/Freebox_OS/core/ajax/Freebox_OS.ajax.php',
		data: {
			action: 'SearchParental'
		},
		dataType: 'json',
		global: false,
		error: function (request, status, error) {
			$('#div_alert').showAlert({
				message: '{{Erreur recherche <b>Contrôle Parental</b>}}',
				level: 'danger'
			});
		},
		success: function (data) {
			$('#div_alert').showAlert({
				message: "{{Opération réalisée avec succès.Appuyez sur Ctrl + F5 (sur Mac CMD + R) si votre écran ne s'est pas actualisé}}",
				level: 'success'

			});
			window.location.reload();
		}
	});

});

$('.eqLogicAction[data-action=tile]').on('click', function () {
	$('#div_alert').showAlert({
		message: '{{Recherche des <b>Tiles</b>}}',
		level: 'warning'
	});
	$.ajax({
		type: 'POST',
		async: true,
		url: 'plugins/Freebox_OS/core/ajax/Freebox_OS.ajax.php',
		data: {
			action: 'SearchTile'
		},
		dataType: 'json',
		global: false,
		error: function (request, status, error) {
			$('#div_alert').showAlert({
				message: '{{Erreur recherche des <b>Tiles</b>}}',
				level: 'danger'
			});
		},
		success: function (data) {
			$('#div_alert').showAlert({
				message: "{{Opération réalisée avec succès. Appuyez sur Ctrl + F5 (sur Mac CMD + R) si votre écran ne s'est pas actualisé}}",
				level: 'success'

			});
			if (!data.result) {
				window.location.reload();
			}
		}
	});

});

$('.Equipement').on('click', function () {
	$('#div_alert').showAlert({
		message: '{{Recherche des <b>commandes</b>}}',
		level: 'warning'
	});
	$.ajax({
		type: 'POST',
		async: false,
		url: 'plugins/Freebox_OS/core/ajax/Freebox_OS.ajax.php',
		data: {
			action: 'Search' + $('.eqLogicAttr[data-l1key=logicalId]').val()
		},
		dataType: 'json',
		global: false,
		error: function (request, status, error) {
			$('#div_alert').showAlert({
				message: '{{Erreur recherche des <b>commandes</b>}}',
				level: 'danger'
			});

		},
		success: function (data) {
			$('#div_alert').showAlert({
				message: "{{Opération réalisée avec succès.}}",
				level: 'success'

			});
			location.reload();
		}
	});
});

$('.eqLogicAttr[data-l1key=configuration][data-l2key=logicalID]').on('change', function () {
	$icon = $('.eqLogicAttr[data-l1key=configuration][data-l2key=logicalID]').value();
	if ($icon != '' && $icon != null)
		$('#img_device').attr("src", 'plugins/Freebox_OS/core/images/' + $icon + '.png');

	var template = $('.eqLogicAttr[data-l1key=logicalId]').val();

	if (template === 'network' || template === 'networkwifiguest') {
		$('.IPV').show();
	} else {
		$('.IPV').hide();
	}
});

$('.eqLogicAttr[data-l1key=configuration][data-l2key=type]').on('change', function () {
	$icon = $('.eqLogicAttr[data-l1key=configuration][data-l2key=type]').value();
	if ($icon != '' && $icon != null)
		$('#img_device').attr("src", 'plugins/Freebox_OS/core/images/' + $icon + '.png');
});

setupPage();

function addCmdToTable(_cmd) {
	if (init(_cmd.logicalId) == 'refresh') {
		return;
	}
	var template = $('.eqLogicAttr[data-l1key=logicalId]').val();
	switch (template) {
		case 'airmedia':
		case 'connexion':
		case 'disk':
		case 'downloads':
		case 'homeadapters':
		case 'network':
		case 'networkwifiguest':
		case 'system':
		case 'wifi':
		case 'phone':
			$('.Equipement').show();
			break;
		default:
			$('.Equipement').hide();
			break;
	}
	if (!isset(_cmd)) {
		var _cmd = {};
	}
	if (!isset(_cmd.configuration)) {
		_cmd.configuration = {};
	}
	var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">';
	tr += '<td>';
	tr += '<span class="cmdAttr" data-l1key="id" ></span>';
	tr += '</td>';
	tr += '<td>';
	tr += '<div class="row">';
	tr += '<div class="col-sm-3">';
	tr += '<a class="cmdAction btn btn-default btn-sm" data-l1key="chooseIcon"><i class="fas fa-flag"></i> Icône</a>';
	tr += '<span class="cmdAttr" data-l1key="display" data-l2key="icon" style="margin-left : 10px;"></span>';
	tr += '</div>';
	tr += '<div class="col-sm-9">';
	tr += '<input class="cmdAttr form-control input-sm" data-l1key="name" placeholder="{{Nom de la commande}}">';
	tr += '</div>';
	tr += '<div class="col-sm-12">';
	tr += '<select class="cmdAttr form-control input-sm disabled" data-l1key="value" style="display : none;margin-top : 5px;" title="{{Commande information liée}}">';
	tr += '<option value="">{{Aucune}}</option>';
	tr += '</select>';
	tr += '</div>';
	tr += '</div>';
	tr += '</td>';
	tr += '<td>';
	tr += '<div class="col-sm-12">';
	tr += '<span class="type disabled" type="' + init(_cmd.type) + '">' + jeedom.cmd.availableType() + '</span>';
    tr += '<span class="subType disabled" subType="' + init(_cmd.subType) + '"></span>';
	tr += '</div>';
	tr += '</td>';
	tr += '<td>';
	if (init(_cmd.subType) == 'numeric' || init(_cmd.subType) == 'slider') {
		tr += '<input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="minValue" placeholder="{{Min}}" title="{{Min}}" style="width : 90px;display : inline-block;"> ';
		tr += '<input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="maxValue" placeholder="{{Max}}" title="{{Max}}" style="width : 90px;display : inline-block;"> ';
		tr += '<input class="cmdAttr form-control input-sm" data-l1key="unite" placeholder="{{Unité}}" title="{{Unité}}" style="width : 90px; display:inline-block"></td>';
	}
	tr += '</td>';
	tr += '<td>';
	tr += '<span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="isVisible" checked/>{{Afficher}}</label></span> ';
	if (_cmd.subType == "binary") {
		tr += '<span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="isHistorized" checked/>{{Historiser}}</label></span> ';
		tr += '<span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="display" data-l2key="invertBinary"/>{{Inverser}}</label></span> ';
	}
	if ((init(_cmd.type) == 'action' && init(_cmd.subType) == 'slider')) {
		tr += '<span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="configuration" data-l2key="invertslide"/>{{Inverser Curseur}}</label></span> ';
	}
	if ((init(_cmd.type) == 'info' && init(_cmd.subType) == 'numeric')) {
		tr += '<span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="configuration" data-l2key="invertnumeric"/>{{Inverser Valeur}}</label></span> ';
	}
	tr += '</td>';
	tr += '<td>';
	if (is_numeric(_cmd.id)) {
		tr += '<a class="btn btn-default btn-xs cmdAction" data-action="configure"><i class="fas fa-cogs"></i></a> ';
		tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fas fa-rss"></i> {{Tester}}</a>';
	}
	tr += '<i class="fas fa-minus-circle pull-right cmdAction cursor" data-action="remove"></i></td>';
	tr += '</tr>';
	$('#table_cmd tbody').append(tr);
	$('#table_cmd tbody tr').last().setValues(_cmd, '.cmdAttr');
	if (isset(_cmd.type)) {
		$('#table_cmd tbody tr:last .cmdAttr[data-l1key=type]').value(init(_cmd.type));
	}
	var tr = $('#table_cmd tbody tr').last();
   jeedom.eqLogic.builSelectCmd({
     id:  $('.eqLogicAttr[data-l1key=id]').value(),
     filter: {type: 'info'},
     error: function (error) {
       $('#div_alert').showAlert({message: error.message, level: 'danger'});
     },
     success: function (result) {
       tr.find('.cmdAttr[data-l1key=value]').append(result);
       tr.setValues(_cmd, '.cmdAttr');
       jeedom.cmd.changeType(tr, init(_cmd.subType));
     }
   });

	jeedom.cmd.changeType($('#table_cmd tbody tr').last(), init(_cmd.subType));
	

}

function setupPage() {
	if (!divEquipements) {
		$(".eqLogicThumbnailDisplay .divEquipements").addClass('freeOSHidenDiv');
	}
	if (!divTiles) {
		$(".eqLogicThumbnailDisplay .divTiles").addClass('freeOSHidenDiv');
	}
	if (!divParental) {
		$(".eqLogicThumbnailDisplay .divParental").addClass('freeOSHidenDiv');
	}

	$.ajax({
		type: "POST",
		url: "plugins/Freebox_OS/core/ajax/Freebox_OS.ajax.php",
		data: {
			action: "GetBox",
		},
		dataType: 'json',
		error: function (request, status, error) {
			handleAjaxError(request, status, error);
		},
		success: function (data) {
			result = data.result.Type_box_tiles;

			if (result !== "OK") {
				$(".titleAction").addClass('freeOSHidenDiv');
			}
		}
	});
}