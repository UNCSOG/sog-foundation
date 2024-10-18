(function (l, a, h) {
    function u(a, c, d) {
        a = "object" !== typeof userSettings ? {} : k.getHash(a + "-" + userSettings.uid) || {};
        if (a.hasOwnProperty(c)) return a[c];
        if (typeof d != "undefined") return d;
        return ""
    }

    function v(a, c, d, i) {
        if ("object" !== typeof userSettings) return !1;
        var g = a + "-time-" + userSettings.uid,
            a = a + "-" + userSettings.uid,
            e = k.getHash(a) || {},
            j = userSettings.url,
            f = c.toString().replace(/[^A-Za-z0-9_]/, ""),
            d = d.toString().replace(/[^A-Za-z0-9_]/, "");
        i ? delete e[f] : e[f] = d;
        k.setHash(a, e, 31536E3, j);
        k.set(g, userSettings.time,
            31536E3, j);
        return c
    }
    a.fn.slideFadeDown = function (b, c, d) {
        return this.each(function () {
            a(this).stop(!0).animate({
                opacity: "show",
                height: "show"
            }, b, c || "linear", function () {
                typeof d === "function" && d.call(this) && d.call(this)
            })
        })
    };
    a.fn.slideFadeUp = function (b, c, d) {
        return this.each(function () {
            a(this).is(":hidden") ? a(this).hide() : a(this).stop(!0).animate({
                opacity: "hide",
                height: "hide"
            }, b, c || "linear", function () {
                typeof d === "function" && d.call(this) && d.call(this)
            })
        })
    };
    a.extend({
        ModManfileDownload: function (b, c) {
            function d() {
                if (document.cookie.indexOf(f.cookieName + "=" + f.cookieValue) != -1) s.onSuccess(b), document.cookie = f.cookieName + "=; expires=" + (new Date(1E3)).toUTCString() + "; path=" + f.cookiePath, g(!1);
                else {
                    if (n || q) try {
                        var c;
                        if ((c = n ? n.document : i(q)) && c.body != null && c.body.innerHTML.length > 0) {
                            var e = !0;
                            if (o && o.length > 0) {
                                var m = a(c.body).contents().first();
                                m.length > 0 && m[0] === o[0] && (e = !1)
                            }
                            if (e) {
                                s.onFail(c.body.innerHTML, b);
                                g(!0);
                                return
                            }
                        }
                    } catch (j) {
                        s.onFail("",
                            b);
                        g(!0);
                        return
                    }
                    setTimeout(d, f.checkInterval)
                }
            }

            function i(a) {
                a = a[0].contentWindow || a[0].contentDocument;
                if (a.document) a = a.document;
                return a
            }

            function g(a) {
                setTimeout(function () {
                    n && (r && n.close(), h && (a ? (n.focus(), n.close()) : n.focus()))
                }, 0)
            }

            function e(a) {
                return a.replace(/&/gm, "&amp;").replace(/\n/gm, "&#10;").replace(/\r/gm, "&#13;").replace(/</gm, "&lt;").replace(/>/gm, "&gt;").replace(/"/gm, "&quot;").replace(/'/gm, "&apos;")
            }
            var j = function () {
                    alert("A file download error has occurred, please try again.")
                },
                f = a.extend({
                    preparingMessageHtml: null,
                    failMessageHtml: null,
                    androidPostUnsupportedMessageHtml: "Unfortunately your Android browser doesn't support this type of file download. Please try again with a different browser.",
                    dialogOptions: {
                        modal: !0
                    },
                    successCallback: function () {},
                    beforeDownloadCallback: !1,
                    failCallback: !1,
                    httpMethod: "GET",
                    data: null,
                    checkInterval: 100,
                    cookieName: "__ModManExportDownload",
                    cookieValue: "true",
                    cookiePath: "/",
                    popupWindowTitle: "Initiating file download...",
                    encodeHTMLEntities: !0
                }, c),
                p = (navigator.userAgent || navigator.vendor || l.opera).toLowerCase(),
                h = !1,
                r = !1,
                k = !1;
            /ip(ad|hone|od)/.test(p) ? h = !0 : p.indexOf("android") != -1 ? r = !0 : k = /avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|playbook|silk|iemobile|iris|kindle|lge |maemo|midp|mmp|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i.test(p) || /1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|e\-|e\/|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(di|rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|xda(\-|2|g)|yas\-|your|zeto|zte\-/i.test(p.substr(0,
                4));
            p = f.httpMethod.toUpperCase();
            if (r && p != "GET") a().dialog ? a("<div>").html(f.androidPostUnsupportedMessageHtml).dialog(f.dialogOptions) : alert(f.androidPostUnsupportedMessageHtml);
            else {
                f.beforeDownloadCallback && f.beforeDownloadCallback();
                var s = {
                    onSuccess: function (a) {
                        f.successCallback(a)
                    },
                    onFail: function (b, d) {
                        f.failMessageHtml ? (a("<div>").html(f.failMessageHtml).dialog(f.dialogOptions), f.failCallback && f.failCallback != j && f.failCallback(b, d)) : f.failCallback && f.failCallback(b, d)
                    }
                };
                if (f.data !== null && typeof f.data !==
                    "string") f.data = a.param(f.data);
                var q, n, o;
                if (p === "GET") f.data !== null && (b.indexOf("?") != -1 ? b.substring(b.length - 1) !== "&" && (b += "&") : b += "?", b += f.data), h || r ? (n = l.open(b), n.document.title = f.popupWindowTitle, l.focus()) : k ? l.location(b) : q = a("<iframe>").hide().attr("src", b).appendTo("body");
                else {
                    var t = "";
                    f.data !== null && a.each(f.data.replace(/\+/g, " ").split("&"), function () {
                        var a = this.split("="),
                            b = f.encodeHTMLEntities ? e(decodeURIComponent(a[0])) : decodeURIComponent(a[0]);
                        if (b) {
                            var d = a[1] || "",
                                d = f.encodeHTMLEntities ?
                                e(decodeURIComponent(a[1])) : decodeURIComponent(a[1]);
                            t += '<input type="hidden" name="' + b + '" value="' + d + '" />'
                        }
                    });
                    k ? (o = a("<form>").appendTo("body"), o.hide().attr("method", f.httpMethod).attr("action", b).html(t)) : (h ? (n = l.open("about:blank"), n.document.title = f.popupWindowTitle, k = n.document, l.focus()) : (q = a("<iframe style='display: none' src='about:blank'></iframe>").appendTo("body"), k = i(q)), k.write("<html><head></head><body><form method='" + f.httpMethod + "' action='" + b + "'>" + t + "</form>" + f.popupWindowTitle +
                        "</body></html>"), o = a(k).find("form"));
                    o.submit()
                }
                setTimeout(d, f.checkInterval)
            }
        }
    });
    var k = {
            each: function (a, c, d) {
                var i, g;
                if (!a) return 0;
                d = d || a;
                if (typeof a.length != "undefined") {
                    i = 0;
                    for (g = a.length; i < g; i++)
                        if (c.call(d, a[i], i, a) === !1) return 0
                } else
                    for (i in a)
                        if (a.hasOwnProperty(i) && c.call(d, a[i], i, a) === !1) return 0; return 1
            },
            getHash: function (a) {
                var a = k.get(a),
                    c;
                a && k.each(a.split("&"), function (a) {
                    a = a.split("=");
                    c = c || {};
                    c[a[0]] = a[1]
                });
                return c
            },
            setHash: function (a, c, d, i, g, e) {
                var j = "";
                k.each(c, function (a, b) {
                    j +=
                        (!j ? "" : "&") + b + "=" + a
                });
                k.set(a, j, d, i, g, e)
            },
            get: function (a) {
                var c = document.cookie,
                    d = a + "=",
                    i;
                if (c) {
                    i = c.indexOf("; " + d);
                    if (i == -1) {
                        if (i = c.indexOf(d), i != 0) return null
                    } else i += 2;
                    a = c.indexOf(";", i);
                    if (a == -1) a = c.length;
                    return decodeURIComponent(c.substring(i + d.length, a))
                }
            },
            set: function (a, c, d, i, g, e) {
                var j = new Date;
                typeof d == "object" && d.toGMTString ? d = d.toGMTString() : parseInt(d, 10) ? (j.setTime(j.getTime() + parseInt(d, 10) * 1E3), d = j.toGMTString()) : d = "";
                document.cookie = a + "=" + encodeURIComponent(c) + (d ? "; expires=" + d :
                    "") + (i ? "; path=" + i : "") + (g ? "; domain=" + g : "") + (e ? "; secure" : "")
            },
            remove: function (a, c) {
                k.set(a, "", -1E3, c)
            }
        },
        e = {
            Popups: {
                pointer: function (b, c) {
                    if (b && b.length) {
                        c || (c = {});
                        var c = a.extend({
                                "class": !1,
                                message: "",
                                callback: !1,
                                position: {
                                    edge: "left",
                                    align: "center",
                                    offset: "15 0"
                                }
                            }, c),
                            d;
                        b.pointer({
                            content: c.message,
                            position: c.position,
                            close: function (d, b) {
                                return function () {
                                    b.callback && typeof b.callback === "function" && b.callback.call(d)
                                }
                            }(b, c),
                            open: function (a, d) {
                                d.pointer.hide().fadeIn("fast")
                            }
                        });
                        d = b.pointer("widget");
                        c["class"] &&
                            d.addClass(c["class"]);
                        b.pointer("open");
                        return b
                    }
                }
            },
            needsSave: !1,
            ajaxurl: h.Settings.ajaxurl,
            sidebars: null,
            the_id: "",
            rem: "",
            win: a(l),
            init: function () {
                var b = isRtl ? "marginRight" : "marginLeft";
                a("#modman-needs-save").hide();
                e.sidebars = a("div.modules-sortables");
                a("#modules-right").on("click", ".sidebar-name", function () {
                    var d = a(this).siblings(".modules-sortables"),
                        b = a(this).parent();
                    b.hasClass("closed") ? (b.removeClass("closed"), d.sortable("enable").sortable("refresh")) : (d.sortable("disable"), b.addClass("closed"))
                });
                a("#modules-left").children(".modules-holder-wrap").children(".sidebar-name").click(function () {
                    a(this).parent().toggleClass("closed")
                });
                a(".wrap").on("click", "a.sidebar-name-arrow", function () {
                    var b = a(this);
                    b.hasClass("modman-open-details") ? (b.removeClass("modman-open-details"), b.parents(".module").first().find(".module-description").slideFadeUp("fast")) : (b.addClass("modman-open-details"), b.parents(".module").first().find(".module-description").slideFadeDown("fast"))
                });
                a("#modules-right").on("click", ".modulemanager-description a.sidebar-name-arrow",
                    function () {
                        var b = a(this).closest(".modulemanager-description").find(".module-info-description");
                        b.hasClass("modman-closed") ? (b.removeClass("modman-closed"), b.slideFadeDown("fast")) : (b.addClass("modman-closed"), b.slideFadeUp("fast"))
                    });
                e.sidebars.each(function () {
                    if (a(this).parent().hasClass("inactive")) return !0;
                    var b = 50,
                        c = a(this).children(".module").length;
                    b += parseInt(c * 48, 10);
                    a(this).css("minHeight", b + "px")
                });
                a("body").on("click", " a.module-action", function () {
                    var d = {},
                        c = a(this).closest("div.module"),
                        g = c.children(".module-inside"),
                        m = parseInt(c.find("input.module-width").val(), 10);
                    if (g.is(":hidden")) {
                        if (m > 250 && g.closest("div.modules-sortables").length) d.width = m + 30 + "px", g.closest("div.modules-liquid-right").length && (d[b] = 235 - m + "px"), c.css(d);
                        e.fixLabels(c);
                        g.slideFadeDown("fast")
                    } else g.slideFadeUp("fast", function () {
                        c.css({
                            width: "",
                            margin: ""
                        })
                    });
                    return !1
                });
                a("body").on("click", "a.module-control-close", function () {
                    e.close(a(this).closest("div.module"));
                    return !1
                });
                a("body").on("click", "a.module-remove",
                    function () {
                        confirm(h.Locale.onModuleRemove) && a(this).closest(".modules-holder-wrap").fadeOut("fast", function () {
                            a(this).remove();
                            e.needsSave = !0;
                            a("#modman-needs-save").show();
                            e.refresh()
                        });
                        return !1
                    });
                a("#modules-right").on("change", ".module-info-description", function () {
                    e.needsSave = !0;
                    a("#modman-needs-save").show()
                });
                a("body").on("click", "a.module-export", function () {
                    e.exportm(a(this));
                    return !1
                });
                a("body").on("click", ".item-not-available .icon-question-sign", function (b) {
                    b.preventDefault();
                    b = a(this);
                    if (b[0].__pointer) return b[0].__pointer.pointer("close"), !1;
                    b[0].__pointer = e.Popups.pointer(b, {
                        message: h.Locale.itemNotAvailableTip,
                        position: {
                            edge: "right",
                            align: "center",
                            offset: "-15 0"
                        },
                        callback: function () {
                            a(this)[0].__pointer = null
                        }
                    });
                    return !1
                });
                e.sidebars.children(".module").each(function () {
                    e.appendTitle(this);
                    a("p.module-error", this).length && a("a.module-action", this).click()
                });
                a("#module-list").find(".module:not(.non-draggable)").draggable({
                    connectToSortable: "div.modules-sortables",
                    handle: "> .module-top > .module-title",
                    distance: 2,
                    helper: "clone",
                    zIndex: 5,
                    containment: "document",
                    start: function (a, b) {
                        b.helper.find("div.module-description").hide();
                        e.the_id = this.id
                    },
                    drag: function (a, b) {
                        var c = e.win.scrollTop(),
                            m = e.win.scrollLeft(),
                            j = e.win.height(),
                            f = e.win.width();
                        j += c;

                        var f = m + f,
                            h = 0,
                            k = 0,
                            l = !1;
                        b.offset.top > j - 100 ? (k = 0.1 * (j - b.offset.top), l = !0) : b.offset.top < c + 100 && (k = 0.1 * (c - b.offset.top), l = !0);
                        b.offset.left > f - 100 ? (h = 0.1 * (f - b.offset.left), l = !0) : b.offset.left < m + 100 && (h = 0.1 * (m - b.offset.left), l = !0);

                        l && e.win[0].scrollBy(h, k)
                    },
                    stop: function () {
                        e.rem &&
                            a(e.rem).hide();
                        jQuery('.module.ui-draggable').css('height','');
                        e.rem = ""
                    }
                });
                e.sidebars.sortable({
                    placeholder: "module-placeholder",
                    items: "> .module",
                    handle: "> .module-top > .module-title",
                    cursor: "move",
                    distance: 2,
                    containment: "document",
                    start: function (a, b) {
                        b.item.children(".module-inside").hide();
                        b.item.css({
                            margin: "",
                            width: ""
                        })
                    },
                    stop: function (b, c) {
                        c.item.hasClass("ui-draggable") && c.item.data("draggable") && c.item.draggable("destroy");
                        if (c.item.hasClass("deleting")) c.item.remove(), e.resize(), e.needsSave = !0, a("#modman-needs-save").show();
                        else {
                            var g =
                                a(this).closest(".modules-holder-wrap");
                            g[0].__pointer && g[0].__pointer.pointer("close");
                            var m = a(c.item);
                            if (a(this).find(".module").not(m).filter(function () {
                                return a(this).find(".module-data .module-item").text() == m.find(".module-data .module-item").text()
                            }).length) c.item.remove();
                            else {
                                var g = parseInt(a("input#__module_cnt__").val(), 10) + 1,
                                    j = e.the_id;
                                a(this).attr("id");
                                a("input#__module_cnt__").val(g + "");
                                c.item.css({
                                    margin: "",
                                    width: ""
                                });
                                e.the_id = "";
                                e.needsSave = !0;
                                a("#modman-needs-save").show();
                                c.item.attr("id",
                                    j.replace("__cnt__", g));
                                c.item.find("a.module-action").click()
                            }
                        }
                    },
                    receive: function (b, c) {
                        var g = a(c.sender);
                        (!a(this).is(":visible") || this.id.indexOf("orphaned_modules") != -1) && g.sortable("cancel");
                        g.attr("id").indexOf("orphaned_modules") != -1 && !g.children(".module").length && g.parents(".orphan-sidebar").slideUp(400, function () {
                            a(this).remove()
                        })
                    }
                }).sortable("option", "connectWith", "div.modules-sortables").parent().filter(".closed").children(".modules-sortables").sortable("disable");
                a("#available-modules").droppable({
                    tolerance: "pointer",
                    accept: function (b) {
                        return !a(b).parents("#module-list").length
                    },
                    drop: function (a, b) {
                        b.draggable.addClass("deleting")
                    },
                    over: function (b, c) {
                        c.draggable.addClass("deleting");
                        a("div.module-placeholder").hide()
                    },
                    out: function (b, c) {
                        c.draggable.removeClass("deleting");
                        a("div.module-placeholder").show()
                    }
                });
                if (1 == u("module-manager", "empty_pointer_tip", 1) && a("#modules-right .modules-holder-wrap").length == 0) {
                    var c = a(".button.modman-add-module");
                    var addpointer=jQuery('.button.modman-add-module');
                    if ( addpointer.length > 0 ) {
                    c[0].__pointer = e.Popups.pointer(c, {
                        message: h.Locale.addNewModuleTip,
                        position: {
                            edge: "right",
                            align: "center",
                            offset: "-15 0"
                        },
                        callback: function () {
                            a(this)[0].__pointer = null;
                            v("module-manager", "empty_pointer_tip", 0)
                        }
                    })
                    }
                }
            },
            save: function () {
                var b = {
                    modules: {}
                };
                a("#modules-right .modules-holder-wrap").length == 0 ? b.modules = [0] : a("#modules-right .modules-holder-wrap").each(function () {
                    var c = a(this).find(".sidebar-name h3").clone().children().remove().end().text(),
                        c = c.trim();
                    b.modules[c] = {};
                    var d = a(this).find(".module-info-description").val();
                    d || (d = "");
                    b.modules[c][h.Settings.moduleInfoKey] = {};
                    b.modules[c][h.Settings.moduleInfoKey].description = d;
                    0 == a(this).find(".modules-sortables .module").length ? b.modules[c] = [0] : a(this).find(".modules-sortables .module").each(function () {
                        var d = a(this).find(".module-data .module-plugin").first().text(),
                            g = a(this).find(".module-data .module-item").first().text(),
                            e = a(this).find(".module-description").html();
                        e || (e = "");
                        var j = a(this).find(".module-title h4").first().clone().children().remove().end().text(),
                            j = j.trim();
                        b.modules[c][d] || (b.modules[c][d] = []);
                        b.modules[c][d].push({
                            id: g,
                            title: j,
                            details: e
                        })
                    })
                });
                b = a.param(b) + "&modman-save-modules-field=" + a("#modman-save-modules-field").val();
                a(".ajax-feedback").css("visibility", "visible");
                a.post(e.ajaxurl, b, function () {
                    a(".ajax-feedback").css("visibility", "hidden");
                    e.needsSave = !1;
                    a("#modman-needs-save").hide()
                })
            },
            appendTitle: function (b) {
                var c = a('input[id*="-title"]', b).val() || "";
                c && (c = ": " + c.replace(/<[^<>]+>/g, "").replace(/</g, "&lt;").replace(/>/g, "&gt;"));
                a(b).children(".module-top").children(".module-title").children().children(".in-module-title").html(c)
            },
            resize: function () {
                a("div.modules-sortables").each(function () {
                    if (a(this).parent().hasClass("inactive")) return !0;
                    var b = 50,
                        c = a(this).children(".module").length;
                    b += parseInt(c * 48, 10);
                    a(this).css("minHeight", b + "px")
                })
            },
            fixLabels: function (b) {
                b.children(".module-inside").find("label").each(function () {
                    var b = a(this).attr("for");
                    b && b == a("input", this).attr("id") && a(this).removeAttr("for")
                })
            },
            close: function (a) {
                a.children(".module-inside").slideUp("fast", function () {
                    a.css({
                        width: "",
                        margin: ""
                    })
                })
            },
            refresh: function () {
                e.sidebars =
                    a("div.modules-sortables");
                e.sidebars.each(function () {
                    if (a(this).parent().hasClass("inactive")) return !0;
                    var b = 50,
                        c = a(this).children(".module").length;
                    b += parseInt(c * 48, 10);
                    a(this).css("minHeight", b + "px")
                });
                e.sidebars.sortable("refresh")
            },
            addNew: function () {
                var b = prompt(h.Locale.newModuleName, "New Module");
                var valid=false;
                var dup= 0;
                if (!b) return !1;
                while (!valid) {

                	/**EMERSON: Module manager 1.6.4+
                	/**Prevent adding modules having the same name
                	 * Because it can overwrite the existing module with the same name when saving.
                	 */

                    /**START*/
                    var mod_object=jQuery('div.modules-holder-wrap .sidebar-name h3');
                    jQuery(mod_object).each(function(i, obj) {
                        var z=jQuery(obj).text();
                        var x = b;

                        /**All lower case comparison */
                        z= z.toLowerCase();
                        x= x.toLowerCase();

                        /**Compare*/
                        if (z === x) {
                        	dup++;
                        }
                    });

                    if ( dup > 0 ) {
                    	//Duplicate module name detected
                    	b = prompt(h.Locale.duplicatenewModuleName, "New Module");
                    	dup= 0;
                    	if (!b) return !1;
                    } else if( /^[a-zA-Z0-9- ]*$/.test( b ) == false ) {
                    	//Illegal characters in module name detected
                    	b = prompt(h.Locale.illegalwModuleName, "New Module");
                    	dup= 0;
                    	if (!b) return !1;
                    } else if ( 0 === dup ) {
                    	//All clear here, proceed.
                    	valid=true;
                    }
                    /**END*/

                }
                var c = b.replace(/\s+/g, "_"),
                    d = a("#module-template").html(),
                    d = a(d.replace("%%__MOD_NAME__%%", b).replace("%%__MOD_ID__%%", c));

                d.appendTo(a("#modules-right"));
                b = a(".button.modman-add-module");
                b[0].__pointer && b[0].__pointer.pointer("close");
                d.find(".modules-sortables").sortable({
                    placeholder: "module-placeholder",
                    items: "> .module",
                    handle: "> .module-top > .module-title",
                    cursor: "move",
                    distance: 2,
                    containment: "document",
                    start: function (a, b) {
                        b.item.children(".module-inside").hide();
                        b.item.css({
                            margin: "",
                            width: ""
                        })
                    },
                    stop: function (b, c) {
                        c.item.hasClass("ui-draggable") && c.item.data("draggable") && c.item.draggable("destroy");
                        if (c.item.hasClass("deleting")) c.item.remove();
                        else {
                            var d = a(this).closest(".modules-holder-wrap");
                            d[0].__pointer && d[0].__pointer.pointer("close");
                            var j = a(c.item);
                            if (a(this).find(".module").not(j).filter(function () {
                                return a(this).find(".module-data .module-item").text() == j.find(".module-data .module-item").text()
                            }).length) c.item.remove();
                            else {
                                var d = c.item.find("input.add_new").val(),
                                    f = c.item.find("input.multi_number").val(),
                                    h = e.the_id;
                                a(this).attr("id");
                                c.item.css({
                                    margin: "",
                                    width: ""
                                });
                                e.the_id = "";
                                d && ("multi" == d ? (c.item.html(c.item.html().replace(/<[^<>]+>/g, function (a) {
                                    return a.replace(/__i__|%i%/g,
                                        f)
                                })), c.item.attr("id", h.replace("__i__", f)), f++, a("div#" + h).find("input.multi_number").val(f)) : "single" == d && (c.item.attr("id", "new-" + h), rem = "div#" + h), c.item.find("input.add_new").val(""), c.item.find("a.module-action").click())
                            }
                        }
                    },
                    receive: function (b, c) {
                        var d = a(c.sender);
                        (!a(this).is(":visible") || this.id.indexOf("orphaned_modules") != -1) && d.sortable("cancel");
                        d.attr("id").indexOf("orphaned_modules") != -1 && !d.children(".module").length && d.parents(".orphan-sidebar").slideUp(400, function () {
                            a(this).remove()
                        })
                    }
                }).sortable("option",
                    "connectWith", "div.modules-sortables").parent().filter(".closed").children(".modules-sortables").sortable("disable");
                e.needsSave = !0;
                if (1 == u("module-manager", "add_pointer_tip", 1)) d[0].__pointer = e.Popups.pointer(d, {
                    message: h.Locale.addElementsTip,
                    position: {
                        edge: "right",
                        align: "center",
                        offset: "-15 0"
                    },
                    callback: function () {
                        a(this)[0].__pointer = null;
                        v("module-manager", "add_pointer_tip", 0)
                    }
                });
                a("#modman-needs-save").show();

                /** EMERSON (New in Module manager 1.4.1+): "Add all items" JS functionality
                 * This will allow users to add all module items available in one click, no need to drag and drop each one of them! */
                /** START */
                /** Needs individualized treatment per created modules basis */

                /** Grab the ID of the newly created module */
                var id_selector='#'+c;

                /** Append the add all button for this newly created empty module */
                jQuery(id_selector).next('.module-controls').children('.module-remove').after('<a id="'+c+'" class="button-primary module-elements-add-all" href="javascript:;">'+ModuleManagerConfig.Locale.addAllItemsText+'</a>');

                /** If there is a dropped module element, remove the add all button, since user wants to add modules manually. */
                jQuery('#modules-right '+id_selector).droppable({
            	    drop: function(event, ui) {
            	    	jQuery(id_selector).next('.module-controls').children('.module-elements-add-all').remove();
            	    }
            	});
                /** END */
                e.refresh()
            },
            filter: function (b) {
                var c = a("#available-modules .module");
                b.trim() == "" ?
                    c.show() : (c.hide(), c.filter(function () {
                        if (a(this).find(".module-title h4").text().toLowerCase().trim().indexOf(b.toLowerCase()) > -1) return !0;
                        return !1
                    }).show())
            },
            moduleData: function (b) {
                data = {
                    module: {
                        name: "",
                        module: {}
                    }
                };
                $module = a(b);
                b = $module.find(".sidebar-name h3").clone().children().remove().end().text();
                b = b.trim();
                data.module.name = b;
                data.module.module = {};
                (b = $module.find(".module-info-description").val()) || (b = "");
                data.module.module[h.Settings.moduleInfoKey] = {};
                data.module.module[h.Settings.moduleInfoKey].description =
                    b;
                if (0 == $module.find(".modules-sortables .module").length) return !1;
                else $module.find(".modules-sortables .module").each(function () {
                    var b = a(this).find(".module-data .module-plugin").first().text(),
                        d = a(this).find(".module-data .module-item").first().text(),
                        e = a(this).find(".module-description").html(),
                        g = a(this).find(".module-title h4").first().clone().children().remove().end().text(),
                        g = g.trim();
                    e || (e = "");
                    data.module.module[b] || (data.module.module[b] = []);
                    data.module.module[b].push({
                        id: d,
                        title: g,
                        details: e
                    })
                });
                return a.param(data)
            },
            exportm: function (b) {
                b = e.moduleData(b.closest(".modules-holder-wrap"));
                !1 !== b ? (a(".ajax-feedback").css("visibility", "visible"), a.ModManfileDownload(h.Settings.exportModuleRoute, {
                    data: b,
                    httpMethod: "POST",
                    successCallback: function () {
                        a(".ajax-feedback").css("visibility", "hidden")
                    },
                    failCallback: function () {
                        a(".ajax-feedback").css("visibility", "hidden");
                        alert(h.Locale.exportErrorMsg)
                    }
                })) : alert(h.Locale.moduleEmptyMsg);
                return !1
            }
        };
    l.onbeforeunload = function (a) {
        if (!a) a = l.event;
        if (e.needsSave) a.cancelBubble = !0,
            a.returnValue = h.Locale.onPageExit, a.stopPropagation && (a.stopPropagation(), a.preventDefault())
    };
    a(function () {
        e.init()
    });
    l.ModuleManager = e
})(window, jQuery, ModuleManagerConfig);
jQuery( function( $ ) {
    function modman_getQueryParams(qs) {
        qs = qs.split("+").join(" ");
        var params = {},
            tokens,
            re = /[?&]?([^=]+)=([^&]*)/g;

        while (tokens = re.exec(qs)) {
            params[decodeURIComponent(tokens[1])]
                = decodeURIComponent(tokens[2]);
        }

        return params;
    }

    $('#toplevel_page_ModuleManager_Modules li a').each(function(){

    	 var href_val=$(this).attr('href');
    	 if ( href_val.length > 0) {
    		 if (href_val=='admin.php?page=ModuleManager_Modules') {
    			 $(this).parent('li').addClass('modman_nav_main');
    		 } else if (href_val=='admin.php?page=ModuleManager_Modules&tab=import') {
    			 $(this).parent('li').addClass('modman_nav_import');
    		 } else if (href_val=='admin.php?page=ModuleManager_Modules&tab=library') {
    			 $(this).parent('li').addClass('modman_nav_library');
    		 }
    	 }
    });

    var modmangetvariables = modman_getQueryParams(document.location.search);
    modman_highlight_current_menu(modmangetvariables);
    function modman_highlight_current_menu(modmangetvariables) {

    	var active_menu =modmangetvariables['tab'];
    	if (active_menu=='import') {
    		$("#toplevel_page_ModuleManager_Modules li.current").removeClass("current");
    		$('#toplevel_page_ModuleManager_Modules .modman_nav_import').addClass('current');
    	} else if (active_menu=='library') {
    		$("#toplevel_page_ModuleManager_Modules li.current").removeClass("current");
    		$('#toplevel_page_ModuleManager_Modules .modman_nav_library').addClass('current');
    	}
    }

     $('form[name="modman-import-form"] #modman-import').attr('disabled','disabled');

     $('form[name="modman-import-form"] input:file').change(function() {
    	 $('form[name="modman-import-form"] #modman-import').removeAttr('disabled');
     });

     /** EMERSON (New in Module manager 1.4.1+): "Add all items" JS functionality
      *  This will allow users to add all module items available in one click, no need to drag and drop each one of them! */
     /** START */
     /** On page load event, looped through existing modules created */

     $('#modules-right .modules-sortables').each(function(i, obj) {
    	 var identifier=this.id;
    	 identifier = identifier.replace(/([ #;?%&,.+*~\':"!^$[\]()=>|\/@])/g,'\\$1');
    	 /** Add "Add all" button if that created module has empty elements */
         if( $('.modules-holder-wrap #'+identifier).is(':empty') ) {
        	 $('#'+identifier).next('.module-controls').children('.module-remove').after('<a id="'+identifier+'" class="button-primary module-elements-add-all" href="javascript:;">'+ModuleManagerConfig.Locale.addAllItemsText+'</a>');
         }
     });

     /** Removal of add all button when element is added should be removed only for that element. */
     $('#modules-right .modules-sortables').droppable({
 	    drop: function(event, ui) {
 	    	 var event_identifier=event.target.id;
 	    	 $('#'+event_identifier).next('.module-controls').children('.module-elements-add-all').remove();
         var showItems = function ( $item ) {
           $item.each( function() {
               var $description = jQuery(this);
               $description.find('.module-description p').first().hide();
               var $others = $description.find( '.module-description' );
               if ( $others.length ) {
                  $description.slideDown( 'fast' );
                  showItems( $others );
               }
           })
         }
         showItems( ui.draggable.find( '.module-description' ).first() );
 	    }
 	});

     $(document).on("click", ".module-elements-add-all", function(){
    	 /** Looper */

    	 /** Grab module unique ID for identification */
    	 var module_identifier=this.id;
    	 $('.modman-module-section .ui-draggable').each(function(i, obj) {

    		    var the_html= $(this)[0].outerHTML
    		    var html_object=$(the_html);

    		    /** Remove all ID attributes */
    		    var html_object=html_object.removeAttr('id');

    		    /** Add block style to .ui-draggable */
    		    var html_object=html_object.filter(".ui-draggable").css("display","block");

    		    /** Add display none to modules inside */
    		    var html_object=html_object.find(".module-inside").css("display","none").addBack('.ui-draggable');

    		    /** Convert to pure HTML */
    		    var html_final=html_object[0].outerHTML

    		    /** Append */
    		    $('#modules-right '+'#'+module_identifier+'.modules-sortables').append(html_final);

    		});

    	 /** Done looping, check if element exist */
    	 if ($('#modules-right '+'#'+module_identifier+'.modules-sortables').find('.module-top').length) {

    	      /** Exist, done remove add all button */
    		 $('.module-controls #'+module_identifier).remove();

    	 }

     });

    jQuery('.modules-list-categories img.img-svg').each(function(){
        var $img = jQuery(this);
        var imgID = $img.attr('id');
        var imgClass = $img.attr('class');
        var imgURL = $img.attr('src');

        jQuery.get(imgURL, function(data) {
            // Get the SVG tag, ignore the rest
            var $svg = jQuery(data).find('svg');

            // Add replaced image's ID to the new SVG
            if(typeof imgID !== 'undefined') {
                $svg = $svg.attr('id', imgID);
            }
            // Add replaced image's classes to the new SVG
            if(typeof imgClass !== 'undefined') {
                $svg = $svg.attr('class', imgClass+' replaced-svg');
            }

            // Remove any invalid XML tags as per http://validator.w3.org
            $svg = $svg.removeAttr('xmlns:a');

            // Replace image with new SVG
            $img.replaceWith($svg);

        }, 'xml');

    });

    jQuery( document ).on( 'click', '.js-module-help-icon', function() {
        var $tooltipTriggerer = jQuery( this )
        jQuery( '.modman-toolset-pointer' ).fadeOut( 100 );
        var tooltipContent = '<h3>' + jQuery(this).data('title') + '</h3>' + jQuery(this).data('content');
        jQuery('.modman-toolset-pointer.wp-pointer-bottom').css( { 'margin-left':'0px', 'margin-top':'0px'});
        $tooltipTriggerer.pointer({
            pointerClass: 'modman-toolset-pointer js-modman-toolset-pointer',//'wp-toolset-pointer wp-toolset-shortcode-pointer
            pointerWidth: 400,
            content: tooltipContent,
            position: {
                edge: 'bottom',
                align: 'right',
                offset: '15 0'
            },
            buttons: function( event, t ) {
                var button_close = $( '<button class="fa modman-dialog-close-button"></button>' );
                button_close.on( 'click.pointer', function( e ) {
                    e.preventDefault();
                    jQuery('.modman-toolset-pointer.wp-pointer-bottom').css( { 'margin-left':'0px', 'margin-top':'0px'});
                    t.element.pointer( 'close' );
                });
                return button_close;
            }
        }).pointer( 'open' );
        jQuery('.modman-toolset-pointer.wp-pointer-bottom').css( {'margin-left':'60px', 'margin-top':'-26px'} );

    });

    jQuery( document ).on( 'click', '.module-download-button-disabled', function() {
        jQuery( this ).parent().find('.js-module-help-icon').trigger( "click" );
    });

     /** END */
});
