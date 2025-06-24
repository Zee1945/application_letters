<div>
    <textarea wire:model="content" class="tinymce-editor" id="tinymce-{{ $editorId }}" name="content"></textarea>
    <script type="module">

//   document.addEventListener('livewire:init', () => {
        tinymce.init({
        selector: '.tinymce-editor',
        target: '#tinymce-{{ $editorId }}',
        // target: $('tinymce-'{{ $editorId }}),
        skin: 'tinymce-5',
        inline_styles: true,
        menubar: 'edit view insert format table',
        font_size_formats: '8pt 9pt 10pt 11pt 12pt 14pt 16pt 18pt 20pt 24pt 36pt',
        plugins: [
            'table', 'lists', 'advlist', 'code', 'anchor', 'charmap', 'pagebreak', 'image', 'searchreplace', 'nonbreaking', 'quickbars', 'visualblocks', 'visualchars', 'fullscreen', 'link'
        ],
        toolbar: [
            'code undo redo | styles fontfamily fontsize lineheight bold italic underline | alignleft aligncenter alignright alignjustify',
            'numlist bullist outdent indent | hr pagebreak nonbreaking image link | table tableprops tablecellprops tablerowprops tablemergecells tablesplitcells tableinsertrowbefore tableinsertrowafter tabledeleterow tableinsertcolbefore tableinsertcolafter tabledeletecol tabledelete'
        ],
        table_toolbar: 'tableprops tablecellprops tablerowprops | tablemergecells tablesplitcells | tableinsertrowbefore tableinsertrowafter tabledeleterow | tableinsertcolbefore tableinsertcolafter tabledeletecol | tabledelete',
        pagebreak_separator: '<p style="page-break-after: always;"></p>',
        image_advtab: true,
        file_picker_types: 'image',
        table_use_colgroups: false,
        table_style_by_css: false,
        line_height_formats: '0 1 1.1 1.2 1.3 1.4 1.5 2',
        font_family_formats: 'Arial=arial,helvetica,sans-serif;Courier New=courier new,courier,monospace;Bookman Old Style=bookman old style;Calibri=Calibri;Times New Roman=times new roman,times;',
        quickbars_insert_toolbar: 'hr pagebreak | image table',
        quickbars_selection_toolbar: false,
        quickbars_image_toolbar: 'alignleft aligncenter alignright | image',
        file_picker_callback: (cb, value, meta) => {
            const input = document.createElement('input');
            input.setAttribute('type', 'file');
            input.setAttribute('accept', 'image/*');

            input.addEventListener('change', (e) => {
                const file = e.target.files[0];

                const reader = new FileReader();
                reader.addEventListener('load', () => {
                    const id = 'blobid' + (new Date()).getTime();
                    const blobCache = tinymce.activeEditor.editorUpload.blobCache;
                    const base64 = reader.result.split(',')[1];
                    const blobInfo = blobCache.create(id, file, base64);
                    blobCache.add(blobInfo);

                    cb(blobInfo.blobUri(), {
                        title: file.name
                    });
                });
                reader.readAsDataURL(file);
            });

            input.click();
        },
        setup: function(editor) {
            editor.on('init', function(e) {
                $(".tox-promotion").remove();
            });
            editor.on('change', () => {
                                
                

            });
        }
    });
//  });

</script>




</div>
