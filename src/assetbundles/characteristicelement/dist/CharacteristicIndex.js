/** global: Craft */
/** global: Garnish */
/**
 * Characteristic index class
 */
Craft.CharacteristicIndex = Craft.BaseElementIndex.extend(
    {
        editableCharacteristics: null,
        $newCategoryBtnGroup: null,
        $newCategoryBtn: null,

        init: function(elementType, $container, settings) {
            this.on('selectSource', $.proxy(this, 'updateButton'));
            this.base(elementType, $container, settings);
        },

        afterInit: function() {
            // Find which of the visible groups the user has permission to create new categories in
            this.editableCharacteristics = [];

            for (var i = 0; i < Characteristic.editableCharacteristicGroups.length; i++) {
                var group = Characteristic.editableCharacteristicGroups[i];

                if (this.getSourceByKey('group:' + group.uid)) {
                    this.editableCharacteristics.push(group);
                }
            }

            this.base();
        },

        getDefaultSourceKey: function() {
            // Did they request a specific category group in the URL?
            if (this.settings.context === 'index' && typeof defaultGroupHandle !== 'undefined') {
                for (var i = 0; i < this.$sources.length; i++) {
                    var $source = $(this.$sources[i]);

                    if ($source.data('handle') === defaultGroupHandle) {
                        return $source.data('key');
                    }
                }
            }

            return this.base();
        },

        updateButton: function() {
            if (!this.$source) {
                return;
            }

            // Get the handle of the selected source
            var selectedSourceHandle = this.$source.data('handle');

            var i, href, label;

            // Update the New Category button
            // ---------------------------------------------------------------------

            if (this.editableCharacteristics.length) {
                // Remove the old button, if there is one
                if (this.$newCategoryBtnGroup) {
                    this.$newCategoryBtnGroup.remove();
                }

                // Determine if they are viewing a group that they have permission to create categories in
                var selectedGroup;

                if (selectedSourceHandle) {
                    for (i = 0; i < this.editableCharacteristics.length; i++) {
                        if (this.editableCharacteristics[i].handle === selectedSourceHandle) {
                            selectedGroup = this.editableCharacteristics[i];
                            break;
                        }
                    }
                }

                this.$newCategoryBtnGroup = $('<div class="btngroup submit"/>');
                var $menuBtn;

                // If they are, show a primary "New category" button, and a dropdown of the other groups (if any).
                // Otherwise only show a menu button
                if (selectedGroup) {
                    href = this._getGroupTriggerHref(selectedGroup);
                    label = (this.settings.context === 'index' ? Craft.t('app', 'New characteristic') : Craft.t('app', 'New {group} characteristic', {group: selectedGroup.name}));
                    this.$newCategoryBtn = $('<a class="btn submit add icon" ' + href + '>' + Craft.escapeHtml(label) + '</a>').appendTo(this.$newCategoryBtnGroup);

                    // if (this.settings.context !== 'index') {
                    //     this.addListener(this.$newCategoryBtn, 'click', function(ev) {
                    //         this._openCreateCategoryModal(ev.currentTarget.getAttribute('data-id'));
                    //     });
                    // }

                    if (this.editableCharacteristics.length > 1) {
                        $menuBtn = $('<div class="btn submit menubtn"></div>').appendTo(this.$newCategoryBtnGroup);
                    }
                }
                else {
                    this.$newCategoryBtn = $menuBtn = $('<div class="btn submit add icon menubtn">' + Craft.t('app', 'New characteristic') + '</div>').appendTo(this.$newCategoryBtnGroup);
                }

                if ($menuBtn) {
                    var menuHtml = '<div class="menu"><ul>';

                    for (i = 0; i < this.editableCharacteristics.length; i++) {
                        var group = this.editableCharacteristics[i];

                        if (this.settings.context === 'index' || group !== selectedGroup) {
                            href = this._getGroupTriggerHref(group);
                            label = (this.settings.context === 'index' ? group.name : Craft.t('app', 'New {group} characteristic', {group: group.name}));
                            menuHtml += '<li><a ' + href + '">' + Craft.escapeHtml(label) + '</a></li>';
                        }
                    }

                    menuHtml += '</ul></div>';

                    $(menuHtml).appendTo(this.$newCategoryBtnGroup);
                    var menuBtn = new Garnish.MenuBtn($menuBtn);

                    if (this.settings.context !== 'index') {
                        menuBtn.on('optionSelect', $.proxy(function(ev) {
                            this._openCreateCategoryModal(ev.option.getAttribute('data-id'));
                        }, this));
                    }
                }

                this.addButton(this.$newCategoryBtnGroup);
            }

            // Update the URL if we're on the Categories index
            // ---------------------------------------------------------------------

            if (this.settings.context === 'index' && typeof history !== 'undefined') {
                var uri = 'characteristics';

                if (selectedSourceHandle) {
                    uri += '/' + selectedSourceHandle;
                }

                history.replaceState({}, '', Craft.getUrl(uri));
            }
        },

        _getGroupTriggerHref: function(group) {
            if (this.settings.context === 'index') {
                var uri = 'characteristics/' + group.handle + '/new';

                return 'href="' + Craft.getUrl(uri) + '"';
            }
            else {
                return 'data-id="' + group.id + '"';
            }
        },
    });

// Register it!
Craft.registerElementIndexClass('venveo\\characteristic\\elements\\Characteristic', Craft.CharacteristicIndex);
