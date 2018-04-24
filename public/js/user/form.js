/**
 *
 */

define(function (require) {
    var template = require('template');
    var common = require('js/lib/common');
    var cropper = require('dist/cropper');
    var uploadFile = require('tpl/uploadModal.tpl');
    var upload = require('upload/main');
    var csrfToken = $('meta[name="csrf-token"]').attr('content');
    var main = {
        init: function () {
            var uploadModal = template.compile(uploadFile)({
                title: '上传头像',
                url: uploadUrl,
            });
            $('#crop-avatar').append(uploadModal);
            jsonFormInit = $("form").serialize();
            this.bindEvent();
        },

        bindEvent: function () {
            $('body').on('click', '.avatar-save', function () {
                var avatar = document.getElementById('avatarInput');
                var filename = avatar.value;
                var fileExtension = filename.substring(filename.lastIndexOf('.') + 1).toLowerCase();
                console.log(fileExtension);
                if (!filename && fileExtension != 'jpg' && fileExtension != 'png' && fileExtension != 'jpeg' && fileExtension != 'gif') {
                    showInfo('请上传正确的图片格式', '180px',2);
                    return false;
                }
            });
//            $("body").on("change", '.user-clinic_id', function () {
//                main.selectRoom($(this));
//            });

        },




    };
    return main;
})