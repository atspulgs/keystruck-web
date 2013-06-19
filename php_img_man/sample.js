$(document).ready(function(){
    $("input#upload_album_payment").change(function() {
        if((this.checked && document.getElementById('album_pay_out').className == "album_pay") 
            || (!this.checked && document.getElementById('album_pay_out').className == "album_pay album_pay_big"))
        {
            $("div#album_pay_out").toggleClass('album_pay_big');
            $("input#album_pay").toggleClass('album_pay_big');
            if(document.getElementById("album_pay").disabled)
                document.getElementById("album_pay").disabled = false;
            else document.getElementById("album_pay").disabled = true;
        }
    });

    $("input[id='photos']").change(function(){
        $("span[id='album_image_error']").hide();
        var er = "";
        for(var i = 0;i<this.files.length;i++){
            if(this.files[i].size > MAX_FILE_SIZE){
                er += this.files[i].name+" is too big: "+Math.round(this.files[i].size/1024/1024)+"MiB!\nMax allowed size is: "+(MAX_FILE_SIZE/1024/1024)+"MiB!\n\n";
                continue;
            }else if(this.files[i].type !='image/jpeg' && this.files[i].type != 'image/gif' && this.files[i].type !='image/png'){
                er += this.files[i].name+" is not a supported Image!\nSupported images are: jpg,png and gif!\n\n";
                continue;
            }
            fileArray.push(this.files[i]);
        }
        if(er != "") alert(er);
        for(var i=fileIndex;i<fileArray.length;i++){
            var code = '<div style="min-height: 60px; position: relative;" class="image_sector"><div title="'+fileArray[i].name+'" style="padding: 5px; float: left;" id="img_area_'+i+'"></div>';
            code += '<div class="separator" style="clear: none;"><input id="idof_'+i+'" type="hidden" name="fileId[]" value=""/><label for="file_name">File Name: </label>';
            code += '<div id="file_name">'+fileArray[i].name+'</div></div><div class="separator" style="clear: right;">';
            code += '<label for="caption">'+$("#secret_album_caption").val()+' :</label>';
            code += '<input name="caption[]" type="text" value="" maxlength="100" id="caption" /></div><div id="progress_'+i+'" class="progress_bar" style="display:none;"><div class="progress">100%</div></div>';
            code += '<span id="album_upload_error_'+i+'"></span>';
            code += '<div title="Remove" class="remove_button" id="remove_button_'+i+'"></div></div>';
            $("div#files").append(code);
            fileIndex++;
            var selector = "img_area_"+i;
            if(fileArray[i].type.match('image.*')) {
                readImage(fileArray[i],selector);
            }
        }
    });

    $("div[id*='remove_button_']").livequery('click', function(event){
        var id = $(this).attr('id').split('_');
        var ind = id[2];
        $("div#remove_button_"+ind).parent().remove();
        fileArray.splice(ind,1);
        for(var i=parseInt(ind);i<fileArray.length;i++){
            $("#remove_button_"+(i+1)).attr('id', 'remove_button_'+i);
            $("#img_area_"+(i+1)).attr('id', 'imag_area_'+i);
            $("#progress_"+(i+1)).attr('id', 'progress_'+i);
        }
        fileIndex--;
    });
    
    $("#upload_submit").click(function(event) {
        event.preventDefault();
        if($("span[id='album_image_error']").is(':visible'))
            $("span[id='album_image_error']").hide();

        var error = false;
        var album_name      = $("input[id='upload_album_name']").val();
        var album_tags      = document.getElementById('upload_album_tags').children;
        var album_category  = $("select[id='upload_album_category']").val();
        var album_paycheck  = document.getElementById('upload_album_payment');
        var album_paybox    = $("input[id='album_pay']").val();

        var name_error      = $("span[id='album_name_error']");
        var tag_error       = $("span[id='album_tag_error']");
        var category_error  = $("span[id='album_category_error']");
        var payment_error   = $("span[id='album_payment_error1']");
        var payment_error2  = $("span[id='album_payment_error2']");
        var photos_error    = $("span[id='album_image_error']");

        var num_regex       = new RegExp("^[0-9]*$");

        if ( album_name == '' ) {
            error   = true;
            $(name_error).fadeIn();            
        } else {
            if ( $(name_error).is(':visible') ) {
                $(name_error).hide();    
            }
        }
        
        var tag_er = true;
        for(i = 0; i<album_tags.length;i++) 
            if(album_tags[i].firstElementChild.checked){
                tag_er = false
                break;
            }

        if(tag_er){
            error   = true;
            $(tag_error).fadeIn();
        } else {
            if ( $(tag_error).is(':visible') ) {
                $(tag_error).hide();
            }    
        }

        if ( album_category == '0' ) {
            error   = true;
            $(category_error).fadeIn();
        } else {
            if ( $(category_error).is(':visible') ) {
                $(category_error).hide();
            }    
        }

        if(album_paycheck.checked){
            if(album_paybox == '' || album_paybox == 0) {
                error   = true;
                $(payment_error).fadeIn();
                if( $(payment_error2).is(':visible') ) {
                    $(payment_error2).hide();
                }
            } else if ( !num_regex.test(album_paybox+"") ){
                error   = true;
                $(payment_error2).fadeIn();
                if( $(payment_error).is(':visible') ) {
                    $(payment_error).hide();
                }
            } else {
                if( $(payment_error).is(':visible') ) {
                    $(payment_error).hide();
                }
                if( $(payment_error2).is(':visible') ) {
                    $(payment_error2).hide();
                }
            }
        }

        if(fileArray.length <= 0){
            error = true;
            $(photos_error).fadeIn();
        } else {
            if ( $(photos_error).is(':visible') ) {
                $(photos_error).hide();
            }    
        }

        if(!error){
            $("input[id='photos']").attr('disabled', 'true');
            $("input[id='upload_submit']").attr('disabled', 'true');
            for(var i=0;i<fileArray.length;i++){
                $("#progress_"+i).slideToggle();
                $("#progress_"+i+" div.progress").html("0%");
                $("#remove_button_"+i).click(function(){});
                $("#remove_button_"+i).hide();
            }
            uploader();
        }
    });
});

function readImage(file, index){
    var reader = new FileReader();
    reader.readAsDataURL(file);
    reader.addEventListener("fileread", imageData);
    reader.onload = function(e) {
        var event = new CustomEvent("fileread", { detail: {result: e.target.result, index: index,}, bubbles: false, cancelable: false});
        this.dispatchEvent(event);
    };
}

function imageData(e){
    document.getElementById(e.detail.index).innerHTML = '<div style="width: 50px; height:50px; overflow: hidden;"><img src="' + e.detail.result + '" height="50px" /></div>';
}

function uploader(){
    if(uploadIndex >= fileArray.length){
        $("#uploadPhoto").submit();
    }else{
        var xhr = new XMLHttpRequest();

        if (!xhr.upload) {
            return false;
        } else {
            if(fileArray.length <= 0) {
                return false;
            } else {
                if (xhr.upload && fileArray[uploadIndex].type == "image/jpeg" && fileArray[uploadIndex].size <= 4194304) {

                    xhr.upload.addEventListener("progress", function(e) {
                        var pc = parseInt(100 - (e.loaded / e.total * 100));
                        var prog = 100-pc;
                        $("#progress_"+uploadIndex+" div.progress").html(prog+"%");
                        $("#progress_"+uploadIndex+" div.progress").css("background-position", (pc + "% 0"));
                    }, false);

                    xhr.onreadystatechange = function(e) {
                        if (xhr.readyState == 4) {
                            response = JSON.parse(xhr.responseText);
                            if(response.error) {
                                $("#progress_"+uploadIndex+" div.progress").html("Error: "+response.code);
                                $("span[id='album_upload_error_"+uploadIndex+"']").html(response.error);
                                $("span[id='album_upload_error_"+uploadIndex+"']").fadeIn();
                            } else {
                                document.getElementById("idof_"+uploadIndex).value = response.pid;
                                var status = (xhr.status == 200 ? "Completed" : "Failed");
                                $("#progress_"+uploadIndex+" div.progress").html(status);
                                $("#progress_"+uploadIndex+" div.progress").css("background-position",(xhr.status == 200 ? "0% 0" : "100% 0"));
                            }
                            uploadIndex++;
                            uploader();
                        }
                    };

                    xhr.open("POST", '../../photoUploader.php', true);
                    xhr.setRequestHeader("X_FILENAME", fileArray[uploadIndex].name);
                    xhr.send(fileArray[uploadIndex]);
                    return true;
                } else {
                    return false;
                }
            }
        }
    }
}
