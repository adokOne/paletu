module_<?php echo $class?> = function(){
<?php if ($use_tree) include "$dir/main/tree.php" ?>
<?php if ($stores):?>
    <?php foreach ($stores as $key => $item):?>
        var <?php echo $key ?>Store = new Ext.data.JsonStore({
            autoLoad:false,
            url:'/admin/<?php echo $class ?>/<?php echo $key ?>_List',
            storeId: '<?php echo $key ?>Store',
            root: 'items',
            idProperty: 'id',
            fields: ['id', 'name']
        });
    <?php endforeach ?>
<?php endif?>

<?php if ($enum_store) echo $enum_store ?>

<?php if ($include) include "$dir/inc/$class.php" ?>

<?php if ($use_logo):?>

   /* var hasLogoCheckColumn = new Ext.grid.CheckColumn({
        header:'L',
        dataIndex:'has_logo',
        align: 'center',
        sortable:true,
        width:30
    });*/

    var croper = new Ext.ux.ImageCroperPanel({
        thumbnail: 'thumbnail',
        preserveRatio:true,
        customWidth: 200,
        customHeight: 200
    });
    
    var imageCropWin = new Ext.Window({
        layout: 'fit',
        title: 'Вибір регіону зображення',
        closable: false,
        modal:true,
        width:620,
        height:300,
        minWidth:300,
        items: new Ext.Panel({
            layout: 'border',
            border:false,
            items:[{
                region:'east',
                width:205,
                baseCls:'x-plain',
                margins:'5 0 5 5',
                html:'<div style="overflow: hidden; width: 200px; height: 200px; border: 1px solid #99BBE8">' +
                     '<img id="thumbnail" src="' + Ext.BLANK_IMAGE_URL + '" />' +
                     '</div>'
            },{
                region:'center',
                margins:'5 0 5 5',
                border:true,
                autoScroll:true,
                layout:'fit',
                items:[croper]
            }]
        }),
        tbar:[{
            text:'Додати',
            tooltip:'Додати',
            iconCls:'add',
            handler:function(){
                    im.plugin = true;
                    im.type = 'image';
                    im.image = 'logo';
                    im.croper = croper;
                    im.createWindow();
            
            }
        },{
            text:'Відміна',
            tooltip:'Відміна',
            iconCls:'remove',
            handler:function(){
                croper.clear();
                imageCropWin.hide();
            }
        }],
        buttonAlign:'center',
        buttons:[{
            text: 'Вибрати',
            handler: function(){
                var region = croper.getCropRegion();
                Ext.Ajax.request({
                    url:'/admin/aaaa/create_logo',
                    success:function(response) {
                        var json = Ext.util.JSON.decode(response.responseText);
                        if(!json.success) {
                            Ext.MessageBox.alert('Помилка', json.msg);
                        } else {
                            editForm.form.findField('temp').setValue(json.logo);
                            Ext.get('logo').dom.src = '<?php echo $logo_path ?>' + json.logo + '<?php echo $logo_prefix.$logo_ext ?>?' + Math.random();
                            croper.clear();
                            imageCropWin.hide();
                        }
                    },
                    params:{
                        width:region.width,
                        height:region.height,
                        top:region.top,
                        left:region.left,
                        src:region.src,
                        id:editForm.form.findField('id').getValue()
                    }
                });
            },
            scope: this
        }]
    });
    
<?php endif ?>



    var translate = function (source, target) {
        var text = editForm.form.findField('desc_'+source).getValue();
        if (text == '')
            return;
            
        Ext.Ajax.request({
            url:'<?php echo $url ?>/translate',
            params:{
                source: source,
                target: target,
                text: text
            },
            method:'post',
            success:function(response, opts) {
                editForm.form.findField('desc_'+target).setValue(response.responseText);
            }
        });
    }
    
    //Base Grid Operations
    var itemSave = function() {
        if (editForm.form.isValid()) {
            editForm.form.items.each(function(f){
                f.getValue();
            });
            editForm.form.submit({
                url:'<?php echo $url ?>/save',
                waitMsg: 'Очікуйте...',
                <?php if ($use_tree): ?>
                params:{
                    node:itemsStore.baseParams.node,
                    fields:itemsStore.baseParams.fields,
                    search:itemsStore.baseParams.search
                },
                <?php endif ?>
                failure:function(form, action) {
                    Ext.MessageBox.alert('Помилка', action.result.msg);
                },
                success:function(form, action) {
                    <?php if ($stores): ?>
                            <?php foreach ($stores as $key => $item): ?>
                                <?php echo $key?>Store.loadData(<?php echo $item ?>);
                            <?php endforeach ?>
                        <?php endif ?>
                    itemsStore.loadData(action.result.list);

                    <?php if ($use_logo):?>
                    Ext.get('logo').set({src:'/<?php echo $logo_path?>'+editForm.form.findField('id').getValue()+'<?php echo $logo_prefix.$logo_ext ?>?' + Math.random()});
                    <?php else:?>
                    Ext.get('logo').set({src:'/<?php echo $logo_path?>'+editForm.form.findField('id').getValue()+'<?php echo $logo_prefix.$logo_ext ?>?' + Math.random()});
                    <?php endif ?>
                    //editWin.hide();
                }
            });
        } else {
            Ext.MessageBox.alert('Помилка', 'Введіть необхідні дані');
        }
    };

    var itemsAdd = function () {
        /*
        editForm.getForm().reset();
        editWin.restore();
        editWin.show();
        var height = editForm.getInnerHeight()*1 + 90;
        if (height > 400) height = 400;
        editWin.setHeight(height);
        <?php if ($use_combo): ?>
        editForm.form.findField('<?php echo $tree_id ?>').setValue(itemsStore.baseParams.node);
        <?php endif ?>
        */
        itemsStore.insert(0, new itemRecord(<?php if ($use_tree): ?>{
            '<?php echo $tree_id ?>':itemsStore.baseParams.node
        }<?php endif ?>));
    }



    var itemsEdit = function () {
        var m = itemsGrid.getSelectionModel().getSelections();
        if(m.length == 1) {        
            itemsAdd();
        
            editForm.form.load({url:'<?php echo $url ?>/edit',
                params:{
                    id:m[0].get('id')
                },
                success:function(response) {
                    <?php if ($use_logo): ?>
                    if (editForm.form.findField('logo').getValue()*1 > 0){
                        Ext.get('logo').set({src:'/<?php echo $logo_path?>'+editForm.form.findField('id').getValue()+'<?php echo $logo_prefix.$logo_ext ?>'});
                    } else {
                        Ext.get('logo').set({src:Ext.BLANK_IMAGE_URL});
                    }
                    <?php endif ?>
                }
            });
        }
    };
        
    var itemsSave = function () {
        var m = itemsStore.getModifiedRecords();
        if(m.length > 0) {
            var jsonData = new Array();
            for(i=0;i<m.length;i++) {
                jsonData[i] = m[i].data;
            }
            var params = {
                'save':Ext.util.JSON.encode(jsonData)
            }
            Ext.apply(itemsStore.baseParams, params);
            var page = 0;
            if (itemsStore.lastOptions && itemsStore.lastOptions.params)
                page = (itemsStore.lastOptions.params.start / itemsGridBBar.pageSize) + 1;
            itemsGridBBar.changePage(page);
            
            
            var saveButton = itemsGrid.getTopToolbar().items.get('itemsSave');
            if (saveButton) saveButton.disable();
                    <?php if ($stores): ?>
                            <?php foreach ($stores as $key => $item): ?>
                                <?php echo $key?>Store.loadData(<?php echo $item ?>);
                            <?php endforeach ?>
                        <?php endif ?>
            itemsStore.commitChanges();
            itemsStore.baseParams.save = null;
        } else {
            Ext.MessageBox.alert('ІнформацІя', 'ДанІ не модифіковані');
        }
    };
	 
    var itemsRemove = function () {
        var m = itemsGrid.getSelectionModel().getSelections();
        if(m.length > 0) {
            Ext.MessageBox.confirm('ІнформацІя', 'Підтвердіть операцію видалення' , function (btn) {
                if(btn == 'yes') {
                    var jsonData = '[{';
                    for(var i = 0, len = m.length; i < len; i++) {
                        var ss = '"'+i+'":"' + m[i].get('id') + '"';
                        if(i==0) {
                            jsonData = jsonData + ss;
                        } else {
                            jsonData = jsonData + ',' + ss;
                        }
                        itemsStore.remove(m[i]);
                    }
                    jsonData = jsonData + '}]';
                    var params = {
                        'remove':jsonData
                    }
                    Ext.apply(itemsStore.baseParams, params);
                    var page = 0;
                    if (itemsStore.lastOptions && itemsStore.lastOptions.params)
                        page = (itemsStore.lastOptions.params.start / itemsGridBBar.pageSize) + 1;
                    itemsGridBBar.changePage(page);

                    itemsStore.baseParams.remove = null;
                }
            });
        }
    };
    
    
    
    // ToolBars
    
    var itemsGridTBar = [
    <?php if ($buttons):?>
    <?php include "$dir/buttons/$class.php" ?>
    <?php else:?>
    {
        tooltip:'Добавить',
        iconCls:'add',
        disabled:false,
        id:'itemsAdd',
        handler:itemsAdd
    }, {
        tooltip:'Видалити виділенні',
        iconCls:'remove',
        disabled:true,
        id:'itemsRemove',
        handler:itemsRemove
    }, {
        tooltip:'Зберігти зміни',
        iconCls:'save',
        disabled:true,
        id:'itemsSave',        
        handler:itemsSave
    }
    <?php endif ?>];

    <?php if ($use_form):?>
    // Edit Form
    var editFormReader = new Ext.data.JsonReader({},[<?php echo $form_columns ?>]);
    
    var editForm = new Ext.FormPanel({
        id:'editform',
        method:'POST',
        region:'center',
        border:true,
        margins:'0 0 0 0',
        layout:'form',
        border:false,
        
        disabled:true,
        
        labelAlign:'top',
        baseCls:'x-plain',
        autoScroll:true,
        reader:editFormReader,
        fileUpload: true,
        items: [
        {
            xtype:'hidden',
            name:'id'
        },
        <?php if ($use_map):?>
        {
            xtype:'hidden',
            id:'latitude',
            name:'latitude'
        },{
            xtype:'hidden',
            id:'longitude',
            name:'longitude'
        },
        <?php endif ?>
         <?php if ($use_google_map):?>
        {
            xtype:'hidden',
            id:'latitude',
            name:'latitude'
        },{
            xtype:'hidden',
            id:'longitude',
            name:'longitude'
        },
        <?php endif ?>
        <?php if ($use_logo) include "$dir/main/form/logo.php" ?>
        <?php if ($use_location) include "$dir/main/form/location.php" ?>
        <?php if (file_exists("$dir/forms/$class.php")) { include "$dir/forms/$class.php";} ?>
        ],
        <?php if (file_exists("$dir/forms/$class.php")): ?>
        tbar:new Ext.Toolbar({
            items:[{
                text:'Зберігти',
                iconCls:'save',
                handler:itemSave
            }]
        }),
        <?php endif ?>
        listeners: {
            <?php if ($use_location): ?>
            actioncomplete: function(form, action){
                if (action.type == 'load') {
                    var values = form.getValues();
                    Ext.getCmp('locationDefault').loadLocation({city: values.city, street: values.street, building: values.building});
                }
            }
            <?php endif ?>
        }
    });
    
    var editWin = new Ext.Window({
        shim:false,
        modal:true,
        maximizable:true,
        title:'Вікно редагування',
        width:700,
        height:400,
        autoHeight:false,
        closeAction:'hide',
        layout:'fit',
        bodyStyle:'overflow-y:auto;padding:10px 16px;',
        items:editForm,
        buttonAlign:'center',
        buttons:[{
            text:'Зберігти',
            iconCls:'save',
            handler:itemSave
        }, {
            text:'Закрыть',
            iconCls:'cancel',
            handler:function(){
                editWin.hide();
            }
        }]
        ,listeners:{
            'hide': function(){
                editForm.form.reset();
                <?php if ($use_logo): ?>
                Ext.get('logo').set({src:Ext.BLANK_IMAGE_URL});
                <?php endif ?>
            }
        }
    });
    <?php endif ?>
      
    var itemRecord = Ext.data.Record.create([
        <?php echo $grid_record ?>
    ]);
   
    var itemsStore = new Ext.data.Store({
        autoLoad:false,
        proxy:new Ext.data.HttpProxy({
            url:'<?php echo $url ?>/list_items'
        }),
        reader:new Ext.data.JsonReader({
            root:'items',
            totalProperty:'total',
            id:'id'
        }, itemRecord),
        remoteSort:true,
        listeners:{
            'update': function(){
                var saveButton = itemsGrid.getTopToolbar().items.get('itemsSave');
                if (this.getModifiedRecords().length > 0 && saveButton){
                    saveButton.enable();
                } else {
                    saveButton.disable();
                }
            }
        }        
    });

    function renderSearch(val){
        if (!val)
            return '';
            
        var res = val+'';
        if (itemsStore.baseParams['search']) {
            var searches = itemsStore.baseParams['search'].split(' ');
            eval('mask = new RegExp(/('+searches.join('|')+')/ig);');
            res = res.replace(new RegExp(mask), '<span style="background-color:yellow;color:red;">$1</span>');
        }
        return res;
    }
    
    function renderIco(val){
        var res = '<img src="<?php echo $logo_path ?>'+ val + '<?php echo $logo_prefix.$logo_ext ?>?' + Math.random() +'" width="16" height="15" alt="" />';
        return res;
    }    

    var itemsGridBBar = new Ext.PagingToolbar({
        store:itemsStore,
        pageSize:20,
        displayInfo:true,
        displayMsg:'{0} - {1} из {2}',
        emptyMsg:'Пусто...'
    });
        
    var itemsGrid = new Ext.grid.EditorGridPanel({
        id:'itemsGrid',
        region:'center',
        margins:'0 0 0 0',
        layout:'fit',
        border:false,
        ds:itemsStore,
        //viewConfig: {forceFit: true},
        split: true,
        //stripeRows: true,
        trackMouseOver:true,
        clicksToEdit: 2,
        //enableHdMenu:false,
        selModel:new Ext.grid.RowSelectionModel({singleSelect:false}),
        sm:new Ext.grid.CheckboxSelectionModel(),
        <?php include "$dir/grids/$class.php" ?>
        tbar:itemsGridTBar,
        bbar:itemsGridBBar
    });
    <?php if ($use_form): ?>
    var editPanel = new Ext.Panel({
        region:'east',
        height:250,
        width:400,
        margins:'0 0 0 0',
        layout:'border',
        border:false,
        split:true,
        items:[<?php if ($use_google_map) include "$dir/main/form/google_map.php" ?><?php if ($use_map) include "$dir/main/form/map.php" ?>editForm]
    });
    <?php endif ?>
    
    itemsGrid.getSelectionModel().on('selectionchange', function(sm) {
        var removeButton = itemsGrid.getTopToolbar().items.get('itemsRemove');
        if(sm.getCount() > 0) {
            <?php if ($use_form): ?>
            var row = sm.getSelections();row = row[0];
            if((sm.getCount() == 1) && row.get('id')) {
                editForm.enable();
                
                editForm.form.items.each(function(f){
                    editForm.form.findField(f.name).setValue(row.get(f.name));
                });

                <?php if ($use_location): ?>
                Ext.getCmp('locationDefault').loadLocation({
                    city:row.get('city'),
                    street:row.get('street'),
                    building:row.get('building')
                });
                <?php endif ?>
                
                <?php if ($use_logo):?>
                if (editForm.form.findField('has_logo').getValue()*1 > 0 ){
                    Ext.get('logo').set({src:'/<?php echo $logo_path ?>'+editForm.form.findField('id').getValue()+'<?php echo $logo_prefix.$logo_ext ?>?'+Math.random()});
                } else {
                    Ext.get('logo').set({src:Ext.BLANK_IMAGE_URL});
                }
                <?php endif ?>
                <?php /*if ($use_logo):?>
                 Ext.get('logo').set({src:'/<?php echo $logo_path ?>'+editForm.form.findField('id').getValue()+'<?php echo $logo_prefix.$logo_ext ?>?'+Math.random()});
                <?php endif;*/?>
                <?php if ($use_google_map):?>
                var lat = row.get('latitude') * 1;
                var lon = row.get('longitude') * 1;
                var title = row.get('name_1');
                if(lat==0 || lon==0){
                	G_map_initialize(50.45074464,30.52292897,"Неопределено");
                }
                else{
                	G_map_initialize(lat,lon,title);
                }
                'pars'
                <?php endif ?>
                <?php if ($use_map):?>
                var lat = row.get('latitude') * 1;
                var lon = row.get('longitude') * 1;
                var title = row.get('street');
                var category = 36;

                var map = document.getElementById('nadoloni');
                if (map.getAttribute('type') == 'application/x-shockwave-flash') {
                    map.clearAll();
                    if ((lat != 0)&&(lon != 0)) {
                        <?php if ($use_lines): ?>
                        map.showPolyLines(row.get('the_geom'));
                        <?php else: ?>
                        map.putMarker(lat, lon, category, title);
                        map.showMarker(0);
                        <?php endif ?>
                    }
                }'pars'
                <?php endif ?>
            } else {
                editForm.disable();
                editForm.getForm().reset();
            }
            <?php endif ?>
            if (removeButton) removeButton.enable();
        } else {
            <?php if ($use_form): ?>
            editForm.getForm().reset();
            editForm.disable();
            <?php endif ?>
            if (removeButton) removeButton.disable();
        }
    });
    
    
    itemsStore.loadData(<?php echo $list_items;?>);
    
    <?php if ($stores): ?>
        <?php foreach ($stores as $key => $item): ?>
            <?php echo $key?>Store.loadData(<?php echo $item ?>);
        <?php endforeach ?>
    <?php endif ?>
