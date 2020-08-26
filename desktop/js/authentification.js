progress(0);
eqLogic_id = null;

$('.bt_Freebox_OS_Next').off('click').on('click', function () {
    funNext();
});

$('.bt_Freebox_OS_Previous').off('click').on('click', function () {
    funPrev();
});

$('.bt_eqlogic_standard').on('click', function () {
    SearchArchi();
    progress(85);
});

$('.bt_eqlogic_tiles').on('click', function () {
    SearchTile();
    progress(90);
});

$('.bt_eqlogic_control_parental').on('click', function () {
    SearchParental();
    progress(95);
});

$('.bt_Freebox_OS_Save').on('click', function () {
    ip = $('#input_freeboxIP').val();
    VersionAPP = $('#input_freeAppVersion').val();
    Categorie = $('#sel_object_default').val();
    SetSetting(ip, VersionAPP, Categorie);
});

$('.bt_Freebox_Autorisation').on('click', function () {
    autorisationFreebox();
});

$('.bt_Freebox_droitVerif').on('click', function () {
    GetSessionData();
});

$('.bt_Freebox_OS_ResetConfig').on('click', function () {
    SetDefaultSetting();
});

$('.bt_Freebox_OS_Save_room').on('click', function () {
    SaveTitelRoom();
});

function updateMenu(objectclass) {
    $('.li_Freebox_OS_Summary.active').removeClass('active');
    $(objectclass).addClass('active');
    $('.Freebox_OS_Display').hide();
    $('.Freebox_OS_Display.' + $(objectclass).attr('data-href')).show();
}

function autorisationFreebox() {
    $.ajax({
        type: "POST",
        url: "plugins/Freebox_OS/core/ajax/Freebox_OS.ajax.php",
        data: {
            action: "connect",
        },
        dataType: 'json',
        error: function (request, status, error) {
            handleAjaxError(request, status, error);
        },
        success: function (data) {
            if (!data.result.success) {
                if (data.result.error_code == "new_apps_denied")
                    $('.textFreebox').text('L\'association de nouvelles applications est désactivée.Merci de modifier les réglages de votre Freebox et relancer ensuite l\'authentification');
                return;
            } else {
                sendToBdd(data.result);
                $('.textFreebox').text('{{Merci d\'appuyer sur le bouton V de votre Freebox, afin de confirmer l\'autorisation d\'accès à votre Freebox.}}');
                $('.img-freeboxOS').attr('src', 'plugins/Freebox_OS/core/images/authentification/authentification.jpg');
                progress(40);
                setTimeout(AskTrackAuthorization, 3000);
            }
        }
    });
}

function SearchArchi() {
    $.ajax({
        type: "POST",
        url: "plugins/Freebox_OS/core/ajax/Freebox_OS.ajax.php",
        data: {
            action: "SearchArchi",
        },
        dataType: 'json',
        error: function (request, status, error) {
            handleAjaxError(request, status, error);
        },
        success: function (data) {

        }
    });
}

function SearchTile() {
    $.ajax({
        type: "POST",
        url: "plugins/Freebox_OS/core/ajax/Freebox_OS.ajax.php",
        data: {
            action: "SearchTile",
        },
        dataType: 'json',
        error: function (request, status, error) {
            handleAjaxError(request, status, error);
        },
        success: function (data) {

        }
    });
}

function SearchTile_Group() {
    $.ajax({
        type: "POST",
        url: "plugins/Freebox_OS/core/ajax/Freebox_OS.ajax.php",
        data: {
            action: "SearchTile_group",
        },
        dataType: 'json',
        error: function (request, status, error) {
            handleAjaxError(request, status, error);
        },
        success: function (data) {
            pieces = data.result.piece;
            object = data.result.objects;
            $("#table_room tr").remove();
            $('#table_room thead').append("<tr><th style=\"width: 320px\">{{Pièces Freebox}}</th><th>{{Objects Jeedom}}</th></tr>");
            for (var i = 0; i < pieces.length; i++) {
                var piece = pieces[i];
                var tr = '<tr class="piece">';
                tr += '<td>';
                tr += '<input class="titleRoomAttr form-control" data-l1key="PieceName" value="' + piece + '" disabled/>';
                tr += '</td>';
                tr += '<td><select id="' + piece + '" class="titleRoomAttr form-control" data-l1key="object_id">' + object + '</td>';
                tr += '</tr>';
                $('#table_room tbody').append(tr);
                value = data.result.config[piece];
                $('#' + piece).val(value);
            }
        }
    });
}

function SearchParental() {
    $.ajax({
        type: "POST",
        url: "plugins/Freebox_OS/core/ajax/Freebox_OS.ajax.php",
        data: {
            action: "SearchParental",
        },
        dataType: 'json',
        error: function (request, status, error) {
            handleAjaxError(request, status, error);
        },
        success: function (data) {

        }
    });
}

function sendToBdd(jsonParser) {
    var fbx_app_token = jsonParser.result.app_token;
    var fbx_track_id = jsonParser.result.track_id;
    $.ajax({
        type: "POST",
        url: "plugins/Freebox_OS/core/ajax/Freebox_OS.ajax.php",
        data: {
            action: "sendToBdd",
            app_token: fbx_app_token,
            track_id: fbx_track_id
        },
        dataType: 'json',
        error: function (request, status, error) {
            handleAjaxError(request, status, error);
        },
        success: function (data) {
            if (!data) {
                $('#div_alert').showAlert({
                    message: data,
                    level: 'danger'
                });
                return;
            }
        }
    });
}

function AskTrackAuthorization() {
    if ($('.li_Freebox_OS_Summary.active').attr('data-href') == "authentification") {

        $('.textFreebox').hide();
        $('.bt_Freebox_OS_Next').hide();
        $('.bt_Freebox_OS_Previous').hide();
        $('.Freebox_OK').hide();
        $('.Freebox_OK_NEXT').hide();

        $.ajax({
            type: "POST",
            url: "plugins/Freebox_OS/core/ajax/Freebox_OS.ajax.php",
            data: {
                action: "ask_track_authorization",
            },
            dataType: 'json',
            error: function (request, status, error) {
                handleAjaxError(request, status, error);
            },
            success: function (data) {
                if (!data.result.success) {
                    $('#div_alert').showAlert({
                        message: data.result.msg,
                        level: 'danger'
                    });
                } else {
                    $('.textFreebox').show();
                    $('.bt_Freebox_OS_Next').show();
                    $('.bt_Freebox_OS_Previous').show();
                    switch (data.result.result.status) {
                        case "unknown":
                            $('.textFreebox').text('{{L\'application a un token invalide ou a été révoqué, il faut relancer l\'authentification. Merci}}');
                            Good();
                            progress(-1);
                            break;
                        case "pending":
                            $('.textFreebox').text('{{Vous n\'avez pas encore validé l\'application sur la Freebox.}}');
                            setTimeout(AskTrackAuthorization, 3000);
                            break;
                        case "timeout":
                            $('.textFreebox').text('{{Vous n\'avez pas validé à temps, il faut relancer l\'authentification. Merci}}');
                            Good();
                            progress(-1);
                            break;
                        case "granted":
                            $('.textFreebox').text('{{Félicitation votre Freebox est maintenant reliée à Jeedom.}}');
                            $('.Freebox_OK').show();
                            $('.Freebox_OK_NEXT').show();
                            $('.Freebox_OS_Display.' + $(this).attr('rights')).show();
                            progress(45);
                            break;
                        case "denied":
                            $('.textFreebox').text('{{Vous avez refusé, il faut relancer l\'authentification. Merci}}');
                            progress(-1);
                            Good();
                            break;
                        default:
                            $('#div_alert').showAlert({
                                message: "REST OK : track_authorization -> Error 4 : Inconnue",
                                level: 'danger'
                            });
                            Good();
                            break;
                    }
                }
            }
        });
    } else {
        $('.textFreebox').show();
        $('.bt_Freebox_OS_Next').show();
        $('.bt_Freebox_OS_Previous').show();
        $('.Freebox_OK').show();
        $('.Freebox_OK_NEXT').show();
    }
}

function Good() {
    $('.bt_Freebox_OS_Previous').hide();
    $('.bt_Freebox_OS_NEXT').hide();
    $('.alert-info Freebox_OK').text('{{Authentification réussi}}');
}

function progress(ProgressPourcent) {
    if (ProgressPourcent == -1) {
        $('#div_progressbar').removeClass('progress-bar-success progress-bar-info progress-bar-warning');
        $('#div_progressbar').addClass('active progress-bar-danger');
        $('#div_progressbar').width('100%');
        $('#div_progressbar').attr('aria-valuenow', 100);
        $('#div_progressbar').html('N/A');
        return;
    }
    if (ProgressPourcent == 100) {
        $('#div_progressbar').removeClass('active progress-bar-info progress-bar-danger progress-bar-warning');
        $('#div_progressbar').addClass('progress-bar-success');
        $('#div_progressbar').width(ProgressPourcent + '%');
        $('#div_progressbar').attr('aria-valuenow', ProgressPourcent);
        $('#div_progressbar').html('FIN');
        return;
    }
    $('#div_progressbar').removeClass('active progress-bar-info progress-bar-danger progress-bar-warning');
    $('#div_progressbar').addClass('progress-bar-success');
    $('#div_progressbar').width(ProgressPourcent + '%');
    $('#div_progressbar').attr('aria-valuenow', ProgressPourcent);
    $('#div_progressbar').html(ProgressPourcent + '%');
}

function GetSetting() {
    $.ajax({
        type: "POST",
        url: "plugins/Freebox_OS/core/ajax/Freebox_OS.ajax.php",
        data: {
            action: "GetSetting",
        },
        dataType: 'json',
        error: function (request, status, error) {
            handleAjaxError(request, status, error);
        },
        success: function (data) {
            $('#input_freeboxIP').val(data.result.ip);
            $('#input_freeAppVersion').val(data.result.VersionAPP);
            $('#input_freeNameAPP').val(data.result.NameAPP);
            $('#input_IdApp').val(data.result.IdApp);
            $('#input_DeviceName').val(data.result.DeviceName);
            $('#sel_object_default').val(data.result.Categorie);
            if (data.result.LogLevel == 100) {
                var debugHides = document.getElementsByClassName('debugFreeOS');
                for (var i = 0; i < debugHides.length; i++) {
                    var debugHide = debugHides[i];
                    debugHide.classList.remove("debugHide");
                }
            } else {
                var debugShows = document.getElementsByClassName('debugFreeOS');
                for (var i = 0; i < debugShows.length; i++) {
                    var debugShow = debugShows[i];
                    debugShow.classList.add("debugHide");
                }
            }
        }
    });
}

function SetSetting(ip, VersionAPP, Categorie) {
    $.ajax({
        type: "POST",
        url: "plugins/Freebox_OS/core/ajax/Freebox_OS.ajax.php",
        data: {
            action: "SetSetting",
            ip: ip,
            VersionAPP: VersionAPP,
            Categorie: Categorie,
        },
        dataType: 'json',
        error: function (request, status, error) {
            handleAjaxError(request, status, error);
        },
        success: function (data) {
            GetSetting();
        }
    });
}

function SetDefaultSetting() {
    $.ajax({
        type: "POST",
        url: "plugins/Freebox_OS/core/ajax/Freebox_OS.ajax.php",
        data: {
            action: "resetSetting",
        },
        dataType: 'json',
        error: function (request, status, error) {
            handleAjaxError(request, status, error);
        },
        success: function (data) {
            GetSetting();
        }
    });
}

function GetSessionData() {

    $('.textFreebox').hide();
    $('.bt_Freebox_OS_Next').hide();
    $('.bt_Freebox_OS_Previous').hide();
    $('.Freebox_OK').hide();
    $('.Freebox_OK_NEXT').hide();
    $('.bt_Freebox_droitVerif').show();

    $.ajax({
        type: "POST",
        url: "plugins/Freebox_OS/core/ajax/Freebox_OS.ajax.php",
        data: {
            action: "GetSessionData",
        },
        dataType: 'json',
        error: function (request, status, error) {
            handleAjaxError(request, status, error);
        },
        success: function (data) {
            if (data.result.success) {
                var permissions = data.result.result.permissions;
                UpdateStatus("calls", permissions.calls);
                UpdateStatus("camera", permissions.camera);
                UpdateStatus("contacts", permissions.contacts);
                UpdateStatus("downloader", permissions.downloader);
                UpdateStatus("explorer", permissions.explorer);
                UpdateStatus("home", permissions.home);
                UpdateStatus("parental", permissions.parental);
                UpdateStatus("player", permissions.player);
                UpdateStatus("profile", permissions.profile);
                UpdateStatus("pvr", permissions.pvr);
                UpdateStatus("settings", permissions.settings);
                UpdateStatus("tv", permissions.tv);
                UpdateStatus("vm", permissions.vm);
                UpdateStatus("wdo", permissions.wdo);

                if (permissions.calls &&
                    permissions.camera &&
                    permissions.downloader &&
                    permissions.home &&
                    permissions.player &&
                    permissions.profile &&
                    permissions.settings) {

                    $('.textFreebox').show();
                    $('.bt_Freebox_OS_Next').show();
                    $('.bt_Freebox_OS_Previous').show();
                    $('.Freebox_OK').show();
                    $('.Freebox_OK_NEXT').show();
                    $('.bt_Freebox_droitVerif').hide();

                    progress(65);
                }
            }
        }
    });
}

function getBox(type) {
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
                if (type == "next") {
                    funNext();
                } else {
                    funPrev()
                }

            } else {
                SearchTile_Group();
            }
        }
    });
}

function UpdateStatus(item, index) {

    if (index == true) {
        document.getElementById(item).classList.add('alert-success');
        document.getElementById(item).classList.remove('alert-danger');
        document.getElementById(item).innerHTML = "OK";
    } else {
        document.getElementById(item).classList.add('alert-danger');
        document.getElementById(item).classList.remove('alert-success');
        document.getElementById(item).innerHTML = "NOK";
    }
}

function SaveTitelRoom() {
    titelRoomArrays = $('#table_room').find('.piece').getValues('.titleRoomAttr');
    $.ajax({
        type: "POST",
        url: "plugins/Freebox_OS/core/ajax/Freebox_OS.ajax.php",
        data: {
            action: "setRoomID",
            data: titelRoomArrays
        },
        dataType: 'json',
        error: function (request, status, error) {
            handleAjaxError(request, status, error);
        },
        success: function (data) {
            SearchTile_Group();
        }
    });
}

function funNext() {
    updateMenu($('.li_Freebox_OS_Summary.active').next());

    switch ($('.li_Freebox_OS_Summary.active').attr('data-href')) {
        case 'home':
            progress(0);
            break;
        case 'setting':
            progress(15);
            GetSetting();
            break;
        case 'authentification':
            progress(25);
            break;
        case 'rights':
            progress(50);
            GetSessionData();
            break;
        case 'room':
            progress(75);
            getBox("next");
            break;
        case 'scan':
            progress(80);
            break;
        case 'end':
            progress(100);
            break;
    }
}

function funPrev() {
    updateMenu($('.li_Freebox_OS_Summary.active').prev());

    switch ($('.li_Freebox_OS_Summary.active').attr('data-href')) {
        case 'home':
            progress(0);
            break;
        case 'setting':
            progress(15);
            GetSetting();
            break;
        case 'authentification':
            progress(25);
            break;
        case 'rights':
            progress(50);
            GetSessionData();
            break;
        case 'room':
            progress(75);
            getBox("prev");
            break;
        case 'scan':
            progress(80);
            break;
        case 'end':
            progress(100);
            break;
    }
}