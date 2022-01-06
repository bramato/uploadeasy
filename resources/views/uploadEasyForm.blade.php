@section('headerplugin')
    <style>
    /* textarea to show files as json */
    textarea#uploaded {
        width: 100%;
        min-height: 300px;
        font-size: 10px;
    }

    #upload-demo {
        padding-bottom: 25px;
        height: 450px;
    }

    figure figcaption {
        position: absolute;
        bottom: 0;
        color: #fff;
        width: 100%;
        padding-left: 9px;
        padding-bottom: 5px;
        text-shadow: 0 0 10px #000;
    }

</style>
    <link rel="stylesheet" href="{{asset('vendor/Croppie-2.6.4/croppie.css')}}"/>
@endsection
@section('main')
    <div class="container-fluid">
<div class="row justify-content-center m-1">
    <div class="col-12">
        <div class="row p-1 imagesRows">
            <div class="col-12 p-0">
                <form action="?" name="search"
                      method="GET"
                      enctype="multipart/form-data">
                    <div class="form-group">
                      <label for="search">{{__('form.Cerca')}}</label>
                      <input type="input" class="form-control" id="search" name="search" placeholder="{{__('form.Cerca')}}">
                    </div>
                </form>
            </div>
        </div>

        {{--                @component('front.component.headtitle',['titolo'=>'Carica Video','sottotitolo'=>'Carica o sostituisci un video','margine'=>'0'])--}}
        {{--                    @if($file!='no')--}}
        {{--                        <form method="POST" action="{{route('ArgomentiContenutiRemoveVideo')}}" enctype="multipart/form-data" name="removevideo">--}}
        {{--                            <input type="hidden" name="_token" value="{{ csrf_token() }}">--}}
        {{--                            <input type="hidden" name="id" value="{{$argomento->id}}">--}}
        {{--                            <button class="btn btn-lg btn-outline-dark" type="submit">Rimuovi Video</button>--}}
        {{--                        </form>--}}
        {{--                    @endif--}}
        {{--                @endcomponent--}}
        <div class="row p-1 imagesRows">
        @foreach($images as $image)
                <div class="col-1 p-1">
            <img class="img img-thumbnail img-fluid imageArchive" data-uuid="{{$image->uuidImage}}" src="{{$image->thumbnail}}">
        </div>
            @endforeach
    </div>
    <div class="row imagesRows">
        <div class="col-12">
            {{$images->render('pagination::simple-default')}}
        </div>
    </div>
    <div class="row">
        <div class="col-12 p-1 m-1 ">
            <div id="upload-demo" class="center-block d-none img-fluid"></div>
        </div>
    <div class="col-12 p-1">
                <form action="{{$url}}"
                      method="POST"
                      enctype="multipart/form-data"
                      class="direct-upload" name="newvideo">

        <label for="file" class="btn btn-info btn-block" id="loadButton"><span><i class="fad fa-image-polaroid"></i> {{__('form.Carica File')}}</span></label>
                        <input type="file" name="file" id="file"  style="display:none" accept="image/x-png,image/gif,image/jpeg">
                    {!! $inputs !!}
                </form>
            <div class="col-12">
                <!-- Progress Bars to show upload completion percentage -->
                <div class="progress-bar-area pt-3 pb-3"></div>
     </div>
                            <button class="btn btn-success btn-block d-none pb-3" id="sendButton"><i class="fa fa-upload"></i> {{__('dashboard.Invia')}}</button>
    </div>

    <div class="col-12 col-lg-10 col-xl-10 m-1">
                <!-- This area will be filled with our results (mainly for debugging) -->
                <div id="uploaded d-none">

                </div>
      <div class="col-12 col-lg-10 col-xl-10">
            </div>
        </div>
    </div>
</div>
</div>
        @endsection
@section('footerplugin')
            <script src="https://code.jquery.com/jquery-3.4.1.min.js"
                    integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo="
                    crossorigin="anonymous"></script>
            <script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
            <!-- Load the uploadcrop Plugin (more info @ https://github.com/blueimp/jQuery-File-Upload) -->
            <script src="https://cdnjs.cloudflare.com/ajax/libs/blueimp-file-upload/9.20.0/js/jquery.fileupload.min.js"></script>
            <script src="{{asset ('vendor/Croppie-2.6.4/croppie.min.js')}}"></script>
            <script>

    $(document).ready(function () {
     $('.imageArchive').click(function(){
      let uuid=($(this).data('uuid'));
      let url=($(this).attr('src'));
      setImage(url,uuid,url);
     });
     // Assigned to variable for later use.
     var form = $('.direct-upload');
     var filesUploaded = [];
     // Place any uploads within the descending folders
     // so ['test1', 'test2'] would become /test1/test2/filename
     var folder= 'media';
     form.fileupload({

      url: form.attr('action'),
      type: form.attr('method'),
      datatype: 'xml',
      add: function (event, data) {

       $('#loadButton').addClass('d-none');
       $('.imagesRows').addClass('d-none');
       $('#upload-demo').removeClass('d-none');
       $('#sendButton').removeClass('d-none');


       $uploadCrop = $('#upload-demo').croppie({
        viewport: {
         width: {{$cropWidth??'150'}},
         height: {{$cropHeight??'150'}},
         @if($cropType==='crcle' or $cropType==='scquare')
         type: '{{$cropType}}'
         @endif
        },
        enableExif: false,
        mouseWheelZoom: true,
        enforceBoundary: true,

       });
       readFile(data);
       $('#sendButton').on('click', function(){
        $uploadCrop.croppie('result', {
         type: 'canvas',
         size: 'original'
        }).then(function (resp) {
         var fileOriginal =data.files[0];
         var file = dataURLtoFile(resp, fileOriginal.name);
         data.files[0] = file;
         file = data.files[0];
         // Give the file which is being uploaded it's current content-type (It doesn't retain it otherwise)
         // and give it a unique name (so it won't overwrite anything already on s3).
         var filename = '{{$uuid}}' + '.' + file.name.split('.').pop();
         form.find('input[name="Content-Type"]').val(file.type);
         form.find('input[name="Content-Length"]').val(file.size);
         form.find('input[name="key"]').val('media/' + '{{$uuid}}/' + filename);
         // Actually submit to form to S3.
         data.submit();
         window.onbeforeunload = function () {
          return '{{__('form.Attenzione, caricamento in corso')}}';
         };
         // Show the progress bar
         // Uses the file size as a unique identifier
         //var bar = $('<div class="progress mb-5" data-mod="' + file.size + '"><div class="bar"></div></div>');
         var bar = $('<div class="progress"><div class="progress-bar" data-mod="' + file.size + '" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div></div>');
         //var bar = $('<div class="progress" data-mod="' + file.size + '"><div class="progress-bar" role="progressbar" style="width: 10%" aria-valuenow="10" aria-valuemin="0" aria-valuemax="100"></div> </div>');
            $("#image_place_{{$id}}", parent.document.body).attr('src','{{asset ('assets/img/loading.jpg')}}').removeClass('d-none');
            $('#sendButton').addClass('d-none');
            $('.progress-bar-area').empty().append(bar);
         bar.slideDown('fast');
        });
        //$("#file").prop("disabled", true);
        // Show warning message if your leaving the page during an upload.

       });
      },

      progress: function (e, data) {
       // This is what makes everything really cool, thanks to that callback
       // you can now update the progress bar based on the upload progress.
       var percent = Math.round((data.loaded / data.total) * 100);
       $('.progress-bar[data-mod="' + data.files[0].size + '"]').css('width', percent + '%').html(percent + '%');
      },
      fail: function (e, data) {
       // Remove the 'unsaved changes' message.
       window.onbeforeunload = null;
       $('.progress[data-mod="' + data.files[0].size + '"]').css('width', '100%').addClass('red').html('');
      },
      done: function (event, data) {
       window.onbeforeunload = null;
       // Upload Complete, show information about the upload in a textarea
       // from here you can do what you want as the file is on S3
       // e.g. save reference to your server using another ajax call or log it, etc.
       var original = data.files[0];
       var s3Result = data.result.documentElement.childNodes;
       filesUploaded.push({
        "original_name": original.name,
        "s3_name": s3Result[2].textContent,
        "size": original.size,
        "url": s3Result[0].textContent.replace("%2F", "/")
       });
       $.ajax({
        url: '{{route('image.save')}}',
        async: true,
        type: 'post',
        data: {
         "_token": '{{ csrf_token() }}',
         "file": s3Result[2].textContent,
         "originalname": original.name,
         "newname": s3Result[2].textContent.split('/').pop(),
         "extention": s3Result[2].textContent.split('.').pop(),
         "uuid": '{{$uuid}}'
        },
        success: function (data) {
         //If the success function is execute,
         //then the Ajax request was successful.
         //Add the data we received in our Ajax
         //request to the "content" div.
         let uuidImage='{{$uuid}}';
         setImage(original.name,uuidImage,data.url);
         $("#chiudiModal{{$id}}", parent.document.body).attr('disabled',false);

        },
        error: function (xhr, ajaxOptions, thrownError) {
         var errorMsg = 'Ajax request failed: ' + xhr.responseText;
         $('#' + id + '_' + val).html(errorMsg);
        }
       });
      }
     });
    });
    function setImage(originalName,uuidImage,url){
     $("#placeImage_{{$id}}", parent.document.body).val(originalName);
     $("#image_{{$id}}", parent.document.body).val(uuidImage);
     $("#image_place_{{$id}}", parent.document.body).attr('src',url).removeClass('d-none');
     $('#uploaded').empty();
     $('#uploaded').append('<div class="avatar"><img src="'+url+'" class="img img-thumbnail"></div>');
        $('.container-fluid').add('d-none');
     parent.$('#imageUpload_{{$id}}').modal('toggle');
     parent.$('.modal-backdrop').remove();
     location.reload();
    }

    function readFile(input) {
     if (input.files && input.files[0]) {
      var reader = new FileReader();

      reader.onload = function (e) {
       $('.upload-demo').addClass('ready');
       $uploadCrop.croppie('bind', {
        url: e.target.result
       }).then(function () {
        console.log('jQuery bind complete');
       });

      };

      reader.readAsDataURL(input.files[0]);
     } else {
      swal("Sorry - you're browser doesn't support the FileReader API");
     }
    }


    function demoUpload() {
     var $uploadCrop;



    }

    function dataURLtoFile(dataurl, filename) {

     var arr = dataurl.split(','),
      mime = arr[0].match(/:(.*?);/)[1],
      bstr = atob(arr[1]),
      n = bstr.length,
      u8arr = new Uint8Array(n);

     while (n--) {
      u8arr[n] = bstr.charCodeAt(n);
     }

     return new File([u8arr], filename, {type: mime});
    }
</script>
@endsection

