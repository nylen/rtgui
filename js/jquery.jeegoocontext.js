﻿// Copyright (c) 2009 - 2010 Erik van den Berg (http://www.planitworks.nl/jeegoocontext)
// Dual licensed under the MIT (http://www.opensource.org/licenses/mit-license.php)
// and GPL (http://www.opensource.org/licenses/gpl-license.php) licenses.
//
// Contributors:
// Denis Evteev
// Roman Imankulov (www.netangels.ru)
//
// Version: 1.3
// Requires jQuery 1.3.2+
(function($){
    var _global;
    var _menus;
    var _nextId = 1;

    // Detect overflow.
    var _overflow = function(x, y){
        return {
            width : x - $(window).width() - $(window).scrollLeft(),
            height : y - $(window).height() - $(window).scrollTop()
        };
    };

    // Keyboard up/down
    var _onKeyUpDown = function(down){
        if(_menus[_global.activeId].currentHover)
        {
            // Hover the first visible menu-item from the next or previous siblings and skip any separator items.
            var selector = ':not(.' + _menus[_global.activeId].separatorClass + ')'
                         + ':not(.' + _menus[_global.activeId].noHoverClass + ')'
                         + ':visible';
            var prevNext = _menus[_global.activeId].currentHover[down ? 'nextAll' : 'prevAll'](selector + ':first');
            // If nothing is found, hover the first or last visible sibling.
            if(prevNext.length == 0)
            {
                prevNext = _menus[_global.activeId].currentHover.parent().find('> li' + selector);
                prevNext = (down ? $(prevNext[0]) : $(prevNext[prevNext.length - 1]));
            }
            prevNext.mouseover();
        }
        else
        {
            // Current hover is null, select the last visible submenu.
            var visibleMenus = $(_global.activeMenu).add('ul', _global.activeMenu).filter(function(){
                return ($(this).is(':visible') && $(this).parents(':hidden').length == 0);
            });
            if(visibleMenus.length > 0)
            {
                // Find all visible menu-items for this menu and hover the first or last visible sibling.
                var visibleItems = $(visibleMenus[visibleMenus.length - 1]).find('> li:visible');
                $(visibleItems[(down ? 0 : (visibleItems.length - 1))]).mouseover();
            }
        }
    };

    // Clear all active context.
    var _clearActive = function(){
        for(cm in _menus)
        {
            $(_menus[cm].allContext).removeClass(_global.activeClass);
        }
    };

    // Reset menu.
    var _resetMenu = function(){
        // Hide active menu and it's submenus.
        if(_global.activeId) {
          $(_global.activeMenu).add('ul', _global.activeMenu).hide();
        }
        // Stop key up/down interval.
        clearInterval(_global.keyUpDown);
        _global.keyUpDownStop = false;
        // Clear current hover.
        if(_menus[_global.activeId]) {
          _menus[_global.activeId].currentHover = null;
        }
        // Clear active menu.
        _global.activeId = null;
        _global.activeMenu = null;
    // Unbind click and mouseover functions bound to the document
    $(document).unbind('click.jeegoocontext').unbind('mouseover.jeegoocontext');
    // Unbind resize event bound to the window.
    $(window).unbind('resize.jeegoocontext');
    };

    var _globalHide = function(e){

    if(!_menus[_global.activeId].allowHide) {
      return false;
    }

        // Invoke onHide callback if set, 'this' refers to the menu.
    // Discontinue default behavior if callback returns false.
    if(_global.activeId && _menus[_global.activeId].onHide)
    {
      if(_menus[_global.activeId].onHide.apply($(_global.activeMenu), [e, _menus[_global.activeId].context]) == false)
      {
        return false;
      }
    }

    // Default behavior.
    // =================================================== //

    // Clear active context.
    _clearActive();
    // Hide active menu.
    _resetMenu();
    };

  $.fn.jeegoocontext = function(selector, options){

        if(!_global) _global = {};
        if(!_menus) _menus = {};

        // Always override _global.menuClass if value is provided by options.
        if(options && options.menuClass)_global.menuClass = options.menuClass;
        // Only set _global.menuClass if not set.
        if(!_global.menuClass)_global.menuClass = 'jeegoocontext';
        // Always override _global.activeClass if value is provided by options.
        if(options && options.activeClass)_global.activeClass = options.activeClass;
        // Only set _global.activeClass if not set.
        if(!_global.activeClass)_global.activeClass = 'active';

        var id = _nextId++;
        var menuEl = this[0];

    // Default undefined:
    // event, string
    // openBelowContext, bool
    // ignoreWidthOverflow, bool
    // ignoreHeightOverflow, bool
    // autoHide, bool
    // onShow, function
    // onHover, function
    // onSelect, function
    // onHide, function
    _menus[id] = $.extend({
            hoverClass: 'hover',
            noHoverClass: 'no-hover',
            noHideClass: 'no-hide',
            submenuClass: 'submenu',
            separatorClass: 'separator',
            operaEvent: 'ctrl+click',
            fadeIn: 200,
            delay: 300,
            keyDelay: 100,
            widthOverflowOffset: 0,
            heightOverflowOffset: 0,
            submenuLeftOffset: 0,
            submenuTopOffset: 0,
            autoAddSubmenuArrows: true,
            startLeftOffset: 0,
            startTopOffset: 0,
            keyboard: true
        }, options || {});

        // All context bound to this menu.
        _menus[id].selector = selector;

        // Add mouseover and click handlers to the menu's items.
        $(document).unbind('.jeegoocontext');
        $('li', menuEl).live('mouseover.jeegoocontext', function(e){

            var $this = _menus[id].currentHover = $(this);

            // Clear hide and show timeouts.
            clearTimeout(_menus[id].show);
            clearTimeout(_menus[id].hide);

            // Clear all hover state.
            $(menuEl).find('*').removeClass(_menus[id].hoverClass);

            // Set hover state on self, direct children, ancestors and ancestor direct children.
            if(!$this.hasClass(_menus[id].noHoverClass)) {
              var $parents = $this.parents('li');
              $this.add($this.find('> *')).add($parents).add($parents.find('> *')).addClass(_menus[id].hoverClass);
            }

            // Invoke onHover callback if set, 'this' refers to the hovered list-item.
            // Discontinue default behavior if callback returns false.
            var continueDefault = true;
            if(_menus[id].onHover)
            {
                if(_menus[id].onHover.apply(this, [e, _menus[id].context]) == false)continueDefault = false;
            }

            // Continue after timeout(timeout is reset on every mouseover).
            if(!_menus[id].proceed)
            {
                _menus[id].show = setTimeout(function(){
                    _menus[id].proceed = true;
                    $this.mouseover();
                }, _menus[id].delay);

                return false;
            }
            _menus[id].proceed = false;

            // Hide all sibling submenu's and deeper level submenu's.
            $this.parent().find('ul').not($this.find('> ul')).hide();

            if(!continueDefault)
            {
                e.preventDefault();
                return false;
            }

            // Default behavior.
            // =================================================== //

            // Position and fade-in submenu's.
            var $submenu = $this.find('> ul');
            if($submenu.length != 0)
            {
                var offSet = $this.offset();

                var overflow = _overflow(
                    (offSet.left + $this.parent().width() + _menus[id].submenuLeftOffset + $submenu.width() + _menus[id].widthOverflowOffset),
                    (offSet.top + _menus[id].submenuTopOffset + $submenu.height() + _menus[id].heightOverflowOffset)
                );
        var parentWidth = $submenu.parent().parent().width();
        var y = offSet.top - $this.parent().offset().top;
                $submenu.css(
                    {
                        'left': (overflow.width > 0 && !_menus[id].ignoreWidthOverflow) ? (-parentWidth - _menus[id].submenuLeftOffset + 'px') : (parentWidth + _menus[id].submenuLeftOffset + 'px'),
                        'top': (overflow.height > 0 && !_menus[id].ignoreHeightOverflow) ? (y - overflow.height + _menus[id].submenuTopOffset) + 'px' : y + _menus[id].submenuTopOffset + 'px'
                    }
                );

                $submenu.fadeIn(_menus[id].fadeIn);
            }
            e.stopPropagation();
        }).live('click.jeegoocontext', function(e){

            // Invoke onSelect callback if set, 'this' refers to the selected listitem.
            // Discontinue default behavior if callback returns false.
            if(_menus[id].onSelect)
            {
                if(_menus[id].onSelect.apply(this, [e, _menus[id].context]) == false)
                {
                    return false;
                }
            }

            // Default behavior.
            //====================================================//

            if($(this).hasClass(_menus[id].noHideClass)) {
              return false;
            }

            // Reset menu
            _resetMenu();

            // Clear active state from this context.
            $(_menus[id].context).removeClass(_global.activeClass);

            e.stopPropagation();
        });

        // Determine the event type used to invoke the menu.
        // Event type is a namespaced event so it can be easily unbound later.
        var div = document.createElement('div');
        div.setAttribute('oncontextmenu', '');
        var eventType = _menus[id].event;
        if(!eventType)
        {
            eventType = (typeof div.oncontextmenu != 'undefined') ? 'contextmenu.jeegoocontext' : _menus[id].operaEvent + '.jeegoocontext';
        }
        else
        {
            eventType += '.jeegoocontext';
        }

        // Searching for the modifier in the event type
        // (e.g. ctrl+click, shift+contextmenu)
        if (eventType.indexOf('+') != -1)
        {
            var chunks = eventType.split('+', 2);
            _menus[id].modifier = chunks[0] + 'Key';
            eventType = chunks[1];
        }

        // Add menu invocation handler to the context.
        return $(selector).live(eventType, function(e){
            // Check for the modifier if any.
      if (typeof _menus[id].modifier == 'string' && !e[_menus[id].modifier]) return;

      // Save context(i.e. the current area to which the menu belongs).
            _menus[id].context = this;
            var $menu = $(menuEl);

            // Determine start position.
            var startLeft, startTop;
            if(_menus[id].openBelowContext)
            {
                var contextOffset = $(this).offset();
                startLeft = contextOffset.left;
                startTop = contextOffset.top + $(this).outerHeight();
            }
            else
            {
                startLeft = e.pageX;
                startTop = e.pageY;
            }
            startLeft += _menus[id].startLeftOffset;
            startTop += _menus[id].startTopOffset;

            // Check for overflow and correct menu-position accordingly.
            var overflow = _overflow((startLeft + $menu.width() + _menus[id].widthOverflowOffset), (startTop + $menu.height() + _menus[id].heightOverflowOffset));
            if(!_menus[id].ignoreWidthOverflow && overflow.width > 0) startLeft -= overflow.width;
            // Ignore y-overflow if openBelowContext or if _menus[id].ignoreHeightOverflow
            if(!_menus[id].openBelowContext && !_menus[id].ignoreHeightOverflow && overflow.height > 0)
            {
                startTop -= overflow.height;
            }

            // Invoke onShow callback if set, 'this' refers to the menu.
            // Discontinue default behavior if callback returns false.
            if(_menus[id].onShow)
            {
                if(_menus[id].onShow.apply($menu, [e, _menus[id].context, startLeft, startTop]) == false)
                {
                    return false;
                }
            }

            // Default behavior.
            // =================================================== //

            // Work around a bug that sometimes causes the menu to be hidden immediately after it is shown.
            _menus[id].allowHide = false;
            window.setTimeout(function() {
              _menus[id].allowHide = true;
            }, 10);

            // Reset last active menu.
            _resetMenu();

            // Set this menu as active menu.
            _global.activeMenu = menuEl;
            _global.activeId = id;

            // Hide current menu and all submenus, on first page load this is neccesary for proper keyboard support.
            $(menuEl).add('ul', menuEl).hide();

            // Clear all active context on page.
            _clearActive();

            // Make this context active.
            $(_menus[id].context).addClass(_global.activeClass);

            // Clear all hover state.
            $menu.find('li, li > *').removeClass(_menus[id].hoverClass);

            // Auto add/delete submenu arrows(spans) if set by options.
            if(_menus[id].autoAddSubmenuArrows)
            {
                $menu.find('li:has(ul)').not(':has(span.' + _menus[id].submenuClass + ')').prepend('<span class="' + _menus[id].submenuClass + '"></span>');
                $menu.find('li').not(':has(ul)').find('> span.' + _menus[id].submenuClass).remove();
            }

            // Fade-in menu at clicked-position.
            $menu.css({
                'left': startLeft + 'px',
                'top':  startTop + 'px'
            }).fadeIn(_menus[id].fadeIn);

      // If openBelowContext, maintain contextmenu left position on window resize event.
            if(_menus[id].openBelowContext)
            {
                $(window).bind('resize.jeegoocontext', function(){
                    $('#' + id).css('left', $(_menus[id].context).offset().left + _menus[id].startLeftOffset + 'px');
                });
            }

      // Bind mouseover, keyup/keydown and click events to the document.
      $(document).bind('mouseover.jeegoocontext', function(e){
        // Remove hovers from last-opened submenu and hide any open relatedTarget submenu's after timeout.
        if($(e.relatedTarget).parents(menuEl).length > 0)
        {
          // Clear show submenu timeout.
          clearTimeout(_menus[id].show);

          var $li = $(e.relatedTarget).parent().find('li');
          $li.add($li.find('> *')).removeClass(_menus[id].hoverClass);

          // Clear last hovered menu-item.
          _menus[_global.activeId].currentHover = null;

          // Set hide submenu timeout.
          _menus[id].hide = setTimeout(function(){
            $li.find('ul').hide();
            if(_menus[id].autoHide)_globalHide(e);
          }, _menus[id].delay);
        }
      }).bind('click.jeegoocontext', _globalHide);

      if(_menus[id].keyboard)
      {
          $(document).bind('keydown.jeegoocontext', function(e){
              switch(e.which)
              {
                  case 38: //keyup
                  if(_global.keyUpDownStop)return false;
                  _onKeyUpDown();
                  _global.keyUpDown = setInterval(_onKeyUpDown, _menus[_global.activeId].keyDelay);
                  _global.keyUpDownStop = true;
                  return false;
                  case 39: //keyright
                  if(_menus[_global.activeId].currentHover)
                  {
                            _menus[_global.activeId].currentHover.find('ul:visible:first li:visible:first').mouseover();
                  }
                  else
                  {
                      var visibleMenus = $(_global.activeMenu).add('ul:visible', _global.activeMenu);
                      if(visibleMenus.length > 0)
                      {
                          $(visibleMenus[visibleMenus.length - 1]).find(':visible:first').mouseover();
                      }
                  }
                  return false;
                  case 40: //keydown
                  if(_global.keyUpDownStop)return false;
                  _onKeyUpDown(true);
                  _global.keyUpDown = setInterval(function(){
                      _onKeyUpDown(true);
                  }, _menus[_global.activeId].keyDelay);
                  _global.keyUpDownStop = true;
                  return false;
                  case 37: //keyleft
                  if(_menus[_global.activeId].currentHover)
                  {
                      $(_menus[_global.activeId].currentHover.parents('li')[0]).mouseover();
                  }
                        else
                        {
                            var hoveredLi = $('li.' + _menus[_global.activeId].hoverClass, _global.activeMenu);
                            if(hoveredLi.length > 0)$(hoveredLi[hoveredLi.length - 1]).mouseover();
                        }
                        return false;
                  case 13: //enter
                  if(_menus[_global.activeId].currentHover)
                  {
                      _menus[_global.activeId].currentHover.click();
                  }
                  else
                  {
                      _globalHide(e);
                  }
                  break;
                  case 27: //escape
                  _globalHide(e);
                  break;
                  default:
                  break;
              }
          }).bind('keyup.jeegoocontext', function(e){
              clearInterval(_global.keyUpDown);
              _global.keyUpDownStop = false;
          });
      }

            return false;
        });
    };

    $.hidejeegoocontext = function() {
      _globalHide();
    };

  // Unbind context from context menu.
    $.fn.nojeegoocontext = function(){
        this.unbind('.jeegoocontext');
    };

})(jQuery);
