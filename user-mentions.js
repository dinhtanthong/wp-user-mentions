jQuery(document).ready(function($) {

    um['autocomplete'] = $('<ul/>', { 'class': 'um-comment-autocomplete' }),
    um['form'] = $('.um-comment-form'),
    um['comment'] = um.form.find('textarea').after(um.autocomplete),
    um['regexp'] = /(?:(^|\W)@)([\w]*?)$/g,
    um['string'] = '';

    um.comment.on('input click keydown', function(e) {
        um.string = (um.comment.val()).substring(0, getCaretPos(um.comment[0]));

        if(e.type == 'keydown' && um.autocomplete.children().length > 0) {
            var selected = um.autocomplete.children('.um-comment-autocomplete-selected').first();

            switch(e.keyCode) {
                case 9:
                case 13:
                    e.preventDefault();
                    um.autocomplete.children('.um-comment-autocomplete-selected').click();
                    return true;
                    break;
                case 38:
                    e.preventDefault();
                    if(!selected.is(":first-child")) selected.removeClass('um-comment-autocomplete-selected').prev().addClass('um-comment-autocomplete-selected');
                    return true;
                    break;
                case 40:
                    e.preventDefault();
                    if(!selected.is(":last-child")) selected.removeClass('um-comment-autocomplete-selected').next().addClass('um-comment-autocomplete-selected');
                    return true;
                    break;
            }
        }

        if(matches = um.regexp.exec(um.string)) {
            var search = matches[2], replace;

            $.post(um.ajaxurl, { 'action': um.action, 'search': search }, function(response) {
                um.autocomplete.empty();

                if(response) {
                    $.each(response, function(i, user) {
                        um.autocomplete.append(
                            $('<li/>', {
                                'class': (!i ? 'um-comment-autocomplete-selected' : ''),
                                'html': (user.display_name !== user.user_login ? user.display_name + ' ' : '') + '@' + user.user_login
                            }).on('click', function() {
                                um.comment.val((um.comment.val()).replace(um.string, replace = (um.string.replace(/((^|\W)@)([\w]*?)$/g, "$1"+user.user_login + " "))));
                                setCaretToPos(um.comment[0], replace.length);
                                um.autocomplete.empty();
                            })
                        );
                    });
                }
            }, 'json');
        } else {
            um.autocomplete.empty();
        }
    });

});

function getCaretPos(node) {
    node.focus();
    if(node.selectionEnd) return node.selectionEnd;
    else if(node.selectionStart) return node.selectionStart;
    else if(!document.selection) return 0;
    var c      = "\001",
        sel    = document.selection.createRange(),
        dul    = sel.duplicate(),
        len    = 0;
    dul.moveToElementText(node);
    sel.text   = c;
    len        = (dul.text.indexOf(c));
    sel.moveStart('character',-1);
    sel.text   = "";
    return len;
}

function setCaretToPos(node, pos) {
    setSelectionRange(node, pos, pos);
}

function setSelectionRange(node, selectionStart, selectionEnd) {
    if(node.setSelectionRange) {
        node.focus();
        node.setSelectionRange(selectionStart, selectionEnd);
    } else if(node.createTextRange) {
        var range = node.createTextRange();
        range.collapse(true);
        range.moveStart('character', selectionStart);
        range.moveEnd('character', selectionEnd);
        range.select();
    }
}