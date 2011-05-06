
jQuery(document).ready(function($) {
    $('div.devel-heading').live('click', function() {
        $(this).toggleClass('devel-collapsed');
        $(this).next().toggle();
    });
});

var LSDevel = LSDevel || {};

LSDevel.Config = {
    enableDebug: true,
	debugCount: 0,
    hasFirebug: false,
	forceFirebug: true,
	isFirefox: (/Firefox/i.test(navigator.userAgent))?true:false,
    cssclass: "#devel-log"
};

LSDevel.Logger = {
    current: null,

    init: function(options) {
        LSDevel.Config = $.extend(LSDevel.Config, options);
    },

    log: function (obj, msgtype) {
        if (LSDevel.Config.enableDebug) {
            if( msgtype == null )
                msgtype = "log";

            if (!LSDevel.Config.hasFirebug) {
                this.traceHtml(obj, msgtype);
            } else {
                try {
                    if( msgtype == "log" )
                        console.log(obj);
                    else if( msgtype == "debug" )
                        console.debug(obj);
                    else if( msgtype == "warn" )
                        console.warn(obj);
                    else if( msgtype == "info" )
                        console.info(obj);
                    else if( msgtype == "error" )
                        console.error(obj);
                } catch(e) {
                    this.traceHtml(obj, msgtype);
                }
            }
        }
    },

    logQuery: function(queryObj) {
        if (LSDevel.Config.enableDebug) {
            if (!LSDevel.Config.hasFirebug) {
                this.traceQueryHtml(queryObj);
            } else {
                try {
                    console.log(queryObj);
                } catch(e) {
                    this.traceQueryHtml(queryObj);
                }
            }
        }
    },

    logQueryTable: function(queryObj) {
        if (LSDevel.Config.enableDebug) {
            if (!LSDevel.Config.hasFirebug) {
                this.traceQueryHtml(queryObj);
            } else {
                try {
                    var columns = [
                        { property:"sql", label: "Sql Query" },
                        { property:"time", label: "Execution Time" },
                    ];
                    console.table(queryObj, columns);
                } catch(e) {
                    this.traceQueryHtml(queryObj);
                }
            }
        }
    },

    startGroup: function(titlename) {
        if (LSDevel.Config.enableDebug) {
            if (!LSDevel.Config.hasFirebug) {
                this.groupStartHtml(titlename);
            } else {
                try {
                    console.group(titlename);
                } catch(e) {
                    this.groupStartHtml(titlename);
                }
            }
        }
    },

    endGroup: function() {
        if (LSDevel.Config.enableDebug) {
            if (!LSDevel.Config.hasFirebug) {
                this.groupEndHtml();
            } else {
                try {
                    console.groupEnd();
                } catch(e) {
                    this.groupEndHtml();
                }
            }
        }
    },

    /* HTML Functions */

    htmlEncode: function(html) {
        return html;
    },

    /* Other Functions */

    traceHtml: function(str, atype) {
        if (LSDevel.Config.enableDebug) {
            if( atype != "html" ) {
                str = String(str);
                str = this.htmlEncode(str);
            }

            this.current.append('<div class="log-entry">' + str + '</div>');

            /*TODO for objects
            LSDevel.Debug.traceHtml(obj);
            for (var i in obj) {
                LSDevel.Debug.trace(i + ": " + obj[i]);
            }*/
        }
    },

    traceQueryHtml: function(queryObj) {
        if (LSDevel.Config.enableDebug) {
            var html = '<div class="query-log">';
            for (var i in queryObj) {
                if( typeof(queryObj[i]['id']) == "undefined" )
                    break;

                var cclass = i % 2 == 0 ? ' query-log-odd' : ' query-log-even';
                if( queryObj[i]['priority'] == 2 )
                    cclass += ' query-log-low';
                else if( queryObj[i]['priority'] == 3 )
                    cclass += ' query-log-high';
                html += '<div class="query-log-entry' + cclass + '"><div class="query-log-number">' + queryObj[i]['id'] + '</div><div class="query-log-query">' + queryObj[i]['sql'] + '</div><div class="query-log-time">' + queryObj[i]['time'] + ' s</div><div class="devel-clear"></div></div>';
            }
            html += '</div>';

            this.current.append(html);
        }
    },

    groupStartHtml: function(titlename) {
        if (LSDevel.Config.enableDebug) {
            str = String(titlename);
            str = this.htmlEncode(str);

            var container = jQuery('<div class="devel-heading">' + str + '</div><div class="devel-container" style="display: block"></div>');

            if( !this.current )
                this.current = jQuery(LSDevel.Config.cssclass);

            container.appendTo(this.current);

            this.current = container.filter('.devel-container');
        }
    },

    groupEndHtml: function() {
        var parent = this.current.parent('.devel-container:first');
        this.current = parent.length > 0 ? parent : null;
    },
};
