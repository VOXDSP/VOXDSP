
/*
 * AdminPanels.js was created solely for this theme.
 * Author: AdminDesigns.com
 * 
 */
;
(function($, window, document, undefined) {

   // Plugin definition.
   $.fn.adminpanel = function(options) {

      // Default plugin options.
      var defaults = {
         grid: '.admin-grid',
         draggable: false,
         mobile: false,
         preserveGrid: false,
         onPanel: function() {
            console.log('callback:', 'onPanel');
         },
         onStart: function() {
            console.log('callback:', 'onStart');
         },
         onSave: function() {
            console.log('callback:', 'onSave');
         },
         onDrop: function() {
            // An "onSave" callback will also be called if
            // the drop also changes the elements DOM position
            console.log('callback:', 'onDrop');
         },
         onFinish: function() {
            console.log('callback:', 'onFinish');
         },
      };

      // Extend default options.
      var options = $.extend({}, defaults, options);

      // Variables.
      var plugin = $(this);
      var pluginID = plugin.attr('id');
      var pluginGrid = options.grid;
      var dragSetting = options.draggable;
      var mobileSetting = options.mobile;
      var preserveSetting = options.preserveGrid;
      var panels = plugin.find('.panel');

      // HTML5 LocalStorage Keys
      var settingsKey = 'panel-settings_' + location.pathname;
      var positionsKey = 'panel-positions_' + location.pathname;

      // HTML5 LocalStorage Gets
      var settingsGet = localStorage.getItem(settingsKey);
      var positionsGet = localStorage.getItem(positionsKey);

      // Control Menu Click Handler
      $('.panel').on('click', '.panel-controls > a', function(e) {
         e.preventDefault();

         // if a panel is being dragged disable menu clicks
         if ($('body.ui-drag-active').length) {
            return;
         }

         // determine appropriate event response
         methods.controlHandlers.call(this, options);
      });

      var methods = {
         init: function(options) {
            var This = $(this);

            // onStart callback
            if (typeof options.onStart == 'function') {
               options.onStart();
            }

            // Check onload to see if positions key is empty
            if (!positionsGet) {
               localStorage.setItem(positionsKey, methods.findPositions());
            } else {
               methods.setPositions();
            }

            // Check onload to see if settings key is empty
            if (!settingsGet) {
               localStorage.setItem(settingsKey, methods.modifySettings());
            }

            // Helper function that adds unique ID's to grid elements 
            $(pluginGrid).each(function(i, e) {
               $(e).attr('id', 'grid-' + i);
            });

            // Check if empty columns should be preserved using an invisible panel
            if (preserveSetting) {
               var Panel = "<div class='panel preserve-grid'></div>";
               $(pluginGrid).each(function(i, e) {
                  $(e).append(Panel);
               });
            }

            // Prep admin panel/container prior to menu creation
            methods.createControls(options);

            // Loop through settings key and apply options to panels
            methods.applySettings();

            // Create Mobile Menus
            methods.createMobileControls(options);

            if (dragSetting === true) {
               // Activate jQuery sortable on declared grids/panels
               plugin.sortable({
                  items: plugin.find('.panel:not(".sort-disable")'),
                  connectWith: pluginGrid,
                  cursor: 'default',
                  revert: 250,
                  handle: '.panel-heading',
                  opacity: 1,
                  delay: 100,
                  tolerance: "pointer",
                  scroll: true,
                  placeholder: 'panel-placeholder',
                  forcePlaceholderSize: true,
                  forceHelperSize: true,
                  start: function(e, ui) {
                     $('body').addClass('ui-drag-active');
                     ui.placeholder.height(ui.helper.outerHeight() - 4);
                  },
                  beforeStop: function() {
                     // onMove callback
                     if (typeof options.onDrop == 'function') {
                        options.onDrop();
                     }
                  },
                  stop: function() {
                     $('body').removeClass('ui-drag-active');
                  },
                  update: function(event, ui) {
                     // toggle loading indicator
                     methods.toggleLoader();

                     // store the positions of the plugins */
                     methods.updatePositions(options);
                  }
               });
            }

            // onFinish callback
            if (typeof options.onFinish == 'function') {
               options.onFinish();
            }
         },
         createMobileControls: function(options) {

            var controls = panels.find('.panel-controls');

            var arr = {};

            $.each(controls, function(i, e) {
               var This = $(e);
               var ID = $(e).parents('.panel').attr('id');

               var controlW = This.width();
               var titleW = This.siblings('.panel-title').width();
               var headingW = This.parent('.panel-heading').width();
               var mobile = (controlW + titleW);
               arr[ID] = mobile;
            });
            console.log(arr)

            $.each(arr, function(i, e) {

               var This = $('#' + i);
               var headingW = This.width() - 75;
               var controls = This.find('.panel-controls');

               if (mobileSetting === true || headingW < e) {
                  This.addClass('mobile-controls');
                  var options = {
                     html: true,
                     placement: "left",
                     content: function(e) {
                        var Content = $(this).clone();
                        return Content;
                     },
                     template: '<div data-popover-id="'+i+'" class="popover" role="tooltip"><div class="arrow"></div><h3 class="popover-title"></h3><div class="popover-content"></div></div>'
                  }
                  controls.popover(options);
               } else {
                  controls.removeClass('mobile-controls');
               }
            });

            // Toggle mobile controls menu open on click
            $('.mobile-controls .panel-heading > .panel-controls').on('click', function() {
               $(this).toggleClass('panel-controls-open');
            });

         },
         applySettings: function(options) {

            // Variables.
            var obj = this;
            var localSettings = localStorage.getItem(settingsKey);
            var parseSettings = JSON.parse(localSettings);

            // Possible panel colors 
            var panelColors = "panel-primary panel-success panel-info panel-warning panel-danger panel-alert panel-system panel-dark panel-default";

            // Pull localstorage obj, parse the data, and then loop
            // through each panel and apply its given settings
            $.each(parseSettings, function(i, e) {

               $.each(e, function(i, e) {
                  var panelID = e['id'];
                  var panelTitle = e['title'];
                  var panelCollapsed = e['collapsed'];
                  var panelHidden = e['hidden'];
                  var panelColor = e['color'];
                  var Target = $('#' + panelID);

                  if (panelTitle) {
                     Target.children('.panel-heading').find('.panel-title').text(panelTitle);
                  }
                  if (panelCollapsed === 1) {
                     Target.addClass('panel-collapsed')
                        .children('.panel-body, .panel-menu, .panel-footer').hide();
                  }
                  if (panelColor) {
                     Target.removeClass(panelColors).addClass(panelColor).attr('data-panel-color', panelColor);
                  }
                  if (panelHidden === 1) {
                     Target.addClass('panel-hidden').hide().remove();
                  }
               });
            });
         },
         createControls: function(options) {

         	// List of available panel controls
            var panelControls = '<span class="panel-controls"></span>';
            var panelTitle = '<a href="#" class="panel-control-title"></a>';
            var panelColor = '<a href="#" class="panel-control-color"></a>';
            var panelCollapse = '<a href="#" class="panel-control-collapse"></a>';
            var panelFullscreen = '<a href="#" class="panel-control-fullscreen"></a>';
            var panelRemove = '<a href="#" class="panel-control-remove"></a>';
            var panelCallback = '<a href="#" class="panel-control-callback"></a>';
            var panelDock = '<a href="#" class="panel-control-dockable" data-toggle="popover" data-content="panelDockContent();"></a>';
            var panelExpose = '<a href="#" class="panel-control-expose"></a>';
            var panelLoader = '<a href="#" class="panel-control-loader"></a>';

            panels.each(function(i, e) {

               var This = $(e);

               // Create panel menu container
               var panelHeader = This.children('.panel-heading');
               $(panelControls).appendTo(panelHeader);

               // Check panel for settings specific attr. Use this
               // value to determine if menu item should be displayed
               var title = This.attr('data-panel-title');
               var color = This.attr('data-panel-color');
               var collapse = This.attr('data-panel-collapse');
               var fullscreen = This.attr('data-panel-fullscreen');
               var remove = This.attr('data-panel-remove');
               var callback = This.attr('data-panel-callback');
               var paneldock = This.attr('data-panel-dockable');
               var expose = This.attr('data-panel-expose');
               var loader = This.attr('data-panel-loader');

               // attach loading indicator like any other button 
               if (!loader) {
                  // Create button
                  var panelMenu = panelHeader.find('.panel-controls');
                  $(panelLoader).appendTo(panelMenu);
               }
               // Upcoming feature, not currently implemented
               if (expose) {
                  // Create button
                  var panelMenu = panelHeader.find('.panel-controls');
                  $(panelExpose).appendTo(panelMenu);
               }
               // Upcoming feature, not currently implemented
               if (paneldock) {
                  // Create button
                  var panelMenu = panelHeader.find('.panel-controls');
                  $(panelDock).appendTo(panelMenu);
               }
               // callback attr must be set true, else icon hidden
               if (callback) {
                  // Create button
                  var panelMenu = panelHeader.find('.panel-controls');
                  $(panelCallback).appendTo(panelMenu);
               }
               if (!remove) {
                  // Create button
                  var panelMenu = panelHeader.find('.panel-controls');
                  $(panelRemove).appendTo(panelMenu);
               }
               if (!title) {
                  // Create button
                  var panelMenu = panelHeader.find('.panel-controls');
                  $(panelTitle).appendTo(panelMenu);
               }
               if (!color) {
                  var panelMenu = panelHeader.find('.panel-controls');
                  $(panelColor).appendTo(panelMenu);
               }
               if (!collapse) {
                  // Create button
                  var panelMenu = panelHeader.find('.panel-controls');
                  $(panelCollapse).appendTo(panelMenu);
               }
               if (!fullscreen) {
                  // Create button
                  var panelMenu = panelHeader.find('.panel-controls');
                  $(panelFullscreen).appendTo(panelMenu);
               }

            });
         },
         controlHandlers: function(e) {

            var This = $(this);

            // Control button indentifiers
            var action = This.attr('class');
            var panel = This.parents('.panel');

            // Panel header variables
            var panelHeading = panel.children('.panel-heading');
            var panelTitle = panel.find('.panel-title');

            // Edit Title definition
            var panelEditTitle = function() {

               // function for toggling the editbox menu
               var toggleBox = function() {
                  var panelEditBox = panel.find('.panel-editbox');
                  panelEditBox.slideToggle('fast', function() {
                     panel.toggleClass('panel-editbox-open');

                     // Save settings to key if editbox is being closed
                     if (!panel.hasClass('panel-editbox-open')) {
                        panelTitle.text(panelEditBox.children('input').val());
                        methods.updateSettings(options);
                     }
                  });
               };

               // If editbox not found, create one and attach handlers
               if (!panel.find('.panel-editbox').length) {
                  var editBox = '<div class="panel-editbox"><input type="text" class="form-control" value="' + panelTitle.text() + '"></div>';
                  panelHeading.after(editBox);

                  // New editbox container
                  var panelEditBox = panel.find('.panel-editbox');

                  // Update panel title on key up
                  panelEditBox.children('input').on('keyup', function() {
                     panelTitle.text(panelEditBox.children('input').val());
                  });

                  // Save panel title on enter key press
                  panelEditBox.children('input').on('keypress', function(e) {
                     if (e.which == 13) {
                        toggleBox();
                     }
                  });

                  // Toggle editbox
                  toggleBox();
               } else {
                  // If found simply toggle the menu
                  toggleBox();
               }
            };

            // Panel color definition
            var panelColor = function() {

               // Create an editbox if one is not found
               if (!panel.find('.panel-colorbox').length) {
                  var colorBox = '<div class="panel-colorbox"> <span class="bg-white" data-panel-color="panel-default"></span> <span class="bg-primary" data-panel-color="panel-primary"></span> <span class="bg-info" data-panel-color="panel-info"></span> <span class="bg-success" data-panel-color="panel-success"></span> <span class="bg-warning" data-panel-color="panel-warning"></span> <span class="bg-danger" data-panel-color="panel-danger"></span> <span class="bg-alert" data-panel-color="panel-alert"></span> <span class="bg-system" data-panel-color="panel-system"></span> <span class="bg-dark" data-panel-color="panel-dark"></span> </div>'
                  panelHeading.after(colorBox);
               }

               // Editbox container
               var panelColorBox = panel.find('.panel-colorbox');

               // Update panel contextual color on click
               panelColorBox.on('click', '> span', function(e) {
                  var dataColor = $(this).data('panel-color');
                  var altColors = 'panel-primary panel-info panel-success panel-warning panel-danger panel-alert panel-system panel-dark panel-default panel-white';
                  panel.removeClass(altColors).addClass(dataColor).data('panel-color', dataColor)
                  methods.updateSettings(options);
               });

               // Toggle elements visability and '.panel-editbox' class
               // If the box is being closed update settings key
               panelColorBox.slideToggle('fast', function() {
                  panel.toggleClass('panel-colorbox-open');
               });

            };

            // Collapse definition
            var panelCollapse = function() {

               // Toggle class
               panel.toggleClass('panel-collapsed');

               // Toggle elements visability
               panel.children('.panel-body, .panel-menu, .panel-footer').slideToggle('fast', function() {
                  methods.updateSettings(options);
               });
            };

            // Fullscreen definition
            var panelFullscreen = function() {
               // If fullscreen mode is active, remove class and enable panel sorting
               if ($('body.panel-fullscreen-active').length) {
                  $('body').removeClass('panel-fullscreen-active');
                  panel.removeClass('panel-fullscreen');
                  if (dragSetting === true) {
                     plugin.sortable("enable");
                  }
               }
               // if not active add fullscreen classes and disable panel sorting
               else {
                  $('body').addClass('panel-fullscreen-active');
                  panel.addClass('panel-fullscreen');
                  if (dragSetting === true) {
                     plugin.sortable("disable");
                  }
               }

               // Hide any open mobile menus or popovers
               $('.panel-controls').removeClass('panel-controls-open');
               $('.popover').popover('hide');

               // Trigger a global window resize to resize any plugins
               // the fullscreened content might contain.
               setTimeout(function() {
                  $(window).trigger('resize');
               }, 100);
            };

            // Remove definition
            var panelRemove = function() {

               // check for Bootbox plugin - should be in core
               if (bootbox.confirm) {
                  bootbox.confirm("Are You Sure?!", function(e) {

                     // e returns true if user clicks "accept"
                     // false if "cancel" or dismiss icon are clicked
                     if (e) {
                        // Timeout simply gives the user a second for the modal to
                        // fade away so they can visibly see the panel disappear
                        setTimeout(function() {
                           panel.addClass('panel-removed').hide();
                           methods.updateSettings(options);
                        }, 200);
                     }

                  });
               } else {
                  panel.addClass('panel-removed').hide();
                  methods.updateSettings(options);
               }
            };

            // Remove definition
            var panelCallback = function() {
               if (typeof options.onPanel == 'function') {
                  options.onPanel();
               }
            };

            // Expose.js definition
            var panelExpose = function() {
               // Code removed, feature will be added to next update
               // once all of the dynamic/responsive aspects of resizing 
               // an exposed panel are worked out                   
            };

            // Responses
            if ($(this).hasClass('panel-control-collapse')) {
               panelCollapse();
            }
            if ($(this).hasClass('panel-control-title')) {
               panelEditTitle();
            }
            if ($(this).hasClass('panel-control-color')) {
               panelColor();
            }
            if ($(this).hasClass('panel-control-fullscreen')) {
               panelFullscreen();
            }
            if ($(this).hasClass('panel-control-remove')) {
               panelRemove();
            }
            if ($(this).hasClass('panel-control-callback')) {
               panelCallback();
            }
            if ($(this).hasClass('panel-control-expose')) {
               panelExpose();
            }
            if ($(this).hasClass('panel-control-dockable')) {
               return
            }
            if ($(this).hasClass('panel-control-loader')) {
               return;
            }

            // Toggle Loader indicator in response to action
            methods.toggleLoader.call(this);

         },
         toggleLoader: function(options) {
            var This = $(this);
            var panel = This.parents('.panel');

            // Add loader to panel
            panel.addClass('panel-loader-active');

            // Remove loader after specified duration
            setTimeout(function() {
               panel.removeClass('panel-loader-active');
            }, 650);

         },
         modifySettings: function(options) {

            // Settings obj
            var settingsArr = [];

            // Determine settings of each panel.
            panels.each(function(i, e) {

               var This = $(e);
               var panelObj = {};

               // Settings variables.
               var panelID = This.attr('id');
               var panelTitle = This.children('.panel-heading').find('.panel-title').text();
               var panelCollapsed = (This.hasClass('panel-collapsed') ? 1 : 0);
               var panelHidden = (This.is(':hidden') ? 1 : 0);
               var panelColor = This.data('panel-color');

               panelObj['id'] = This.attr('id');
               panelObj['title'] = This.children('.panel-heading').find('.panel-title').text();
               panelObj['collapsed'] = (This.hasClass('panel-collapsed') ? 1 : 0);
               panelObj['hidden'] = (This.is(':hidden') ? 1 : 0);
               panelObj['color'] = (panelColor ? panelColor : null);

               settingsArr.push({
                  'panel': panelObj
               });
            });

            var checkedSettings = JSON.stringify(settingsArr);

            // Log Results
            // console.log('Key contains: ', checkedSettings);

            // return panel positions array
            return checkedSettings;
         },
         findPositions: function(options) {

            var grids = plugin.find(pluginGrid);
            var gridsArr = [];

            // Determine panels present.
            grids.each(function(index, ele) {

               var panels = $(ele).find('.panel');
               var panelArr = [];

               $(ele).attr('id', 'grid-' + index);

               panels.each(function(i, e) {
                  var panelID = $(e).attr('id');
                  panelArr.push(panelID);
               });

               gridsArr[index] = panelArr;
            });

            var checkedPosition = JSON.stringify(gridsArr);

            // return panel positions array
            return checkedPosition;

         },
         setPositions: function(options) {

            // Variables
            var obj = this;
            var localPositions = localStorage.getItem(positionsKey);
            var parsePosition = JSON.parse(localPositions);

            // Pull localstorage obj, parse the data, and then loop
            // through each panel and set its position
            $(pluginGrid).each(function(i, e) {
               var rowID = $(e)
               $.each(parsePosition[i], function(i, ele) {
                  $('#' + ele).appendTo(rowID);
               });
            });
         },
         updatePositions: function(options) {
            localStorage.setItem(positionsKey, methods.findPositions());

            // onSave callback
            if (typeof options.onSave == 'function') {
               options.onSave();
            }
         },
         updateSettings: function(options) {
            localStorage.setItem(settingsKey, methods.modifySettings());

            // onSave callback
            if (typeof options.onSave == 'function') {
               options.onSave();
            }
         }
      }

      // Plugin implementation
      return this.each(function() {
         methods.init.call(plugin, options);
      });

   };

})(jQuery, window, document);
