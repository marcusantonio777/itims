var Es = { exports: {} }, en = {};
/**
 * @license React
 * react-jsx-runtime.production.js
 *
 * Copyright (c) Meta Platforms, Inc. and affiliates.
 *
 * This source code is licensed under the MIT license found in the
 * LICENSE file in the root directory of this source tree.
 */
var am;
function fy() {
  if (am) return en;
  am = 1;
  var z = Symbol.for("react.transitional.element"), B = Symbol.for("react.fragment");
  function Q(r, I, U) {
    var Al = null;
    if (U !== void 0 && (Al = "" + U), I.key !== void 0 && (Al = "" + I.key), "key" in I) {
      U = {};
      for (var $l in I)
        $l !== "key" && (U[$l] = I[$l]);
    } else U = I;
    return I = U.ref, {
      $$typeof: z,
      type: r,
      key: Al,
      ref: I !== void 0 ? I : null,
      props: U
    };
  }
  return en.Fragment = B, en.jsx = Q, en.jsxs = Q, en;
}
var um;
function sy() {
  return um || (um = 1, Es.exports = fy()), Es.exports;
}
var o = sy(), bs = { exports: {} }, X = {};
/**
 * @license React
 * react.production.js
 *
 * Copyright (c) Meta Platforms, Inc. and affiliates.
 *
 * This source code is licensed under the MIT license found in the
 * LICENSE file in the root directory of this source tree.
 */
var em;
function oy() {
  if (em) return X;
  em = 1;
  var z = Symbol.for("react.transitional.element"), B = Symbol.for("react.portal"), Q = Symbol.for("react.fragment"), r = Symbol.for("react.strict_mode"), I = Symbol.for("react.profiler"), U = Symbol.for("react.consumer"), Al = Symbol.for("react.context"), $l = Symbol.for("react.forward_ref"), bl = Symbol.for("react.suspense"), pl = Symbol.for("react.memo"), p = Symbol.for("react.lazy"), E = Symbol.for("react.activity"), H = Symbol.for("react.view_transition"), jl = Symbol.iterator;
  function et(v) {
    return v === null || typeof v != "object" ? null : (v = jl && v[jl] || v["@@iterator"], typeof v == "function" ? v : null);
  }
  var Wl = {
    isMounted: function() {
      return !1;
    },
    enqueueForceUpdate: function() {
    },
    enqueueReplaceState: function() {
    },
    enqueueSetState: function() {
    }
  }, dl = Object.assign, Tt = {};
  function nt(v, N, C) {
    this.props = v, this.context = N, this.refs = Tt, this.updater = C || Wl;
  }
  nt.prototype.isReactComponent = {}, nt.prototype.setState = function(v, N) {
    if (typeof v != "object" && typeof v != "function" && v != null)
      throw Error(
        "takes an object of state variables to update or a function which returns an object of state variables."
      );
    this.updater.enqueueSetState(this, v, N, "setState");
  }, nt.prototype.forceUpdate = function(v) {
    this.updater.enqueueForceUpdate(this, v, "forceUpdate");
  };
  function wa() {
  }
  wa.prototype = nt.prototype;
  function Bt(v, N, C) {
    this.props = v, this.context = N, this.refs = Tt, this.updater = C || Wl;
  }
  var Gt = Bt.prototype = new wa();
  Gt.constructor = Bt, dl(Gt, nt.prototype), Gt.isPureReactComponent = !0;
  var it = Array.isArray;
  function K() {
  }
  var tl = { H: null, A: null, T: null, S: null }, Xt = Object.prototype.hasOwnProperty;
  function Et(v, N, C) {
    var R = C.ref;
    return {
      $$typeof: z,
      type: v,
      key: N,
      ref: R !== void 0 ? R : null,
      props: C
    };
  }
  function bt(v, N) {
    return Et(v.type, N, v.props);
  }
  function ct(v) {
    return typeof v == "object" && v !== null && v.$$typeof === z;
  }
  function ga(v) {
    var N = { "=": "=0", ":": "=2" };
    return "$" + v.replace(/[=:]/g, function(C) {
      return N[C];
    });
  }
  var Fa = /\/+/g;
  function Ul(v, N) {
    return typeof v == "object" && v !== null && v.key != null ? ga("" + v.key) : N.toString(36);
  }
  function O(v) {
    switch (v.status) {
      case "fulfilled":
        return v.value;
      case "rejected":
        throw v.reason;
      default:
        switch (typeof v.status == "string" ? v.then(K, K) : (v.status = "pending", v.then(
          function(N) {
            v.status === "pending" && (v.status = "fulfilled", v.value = N);
          },
          function(N) {
            v.status === "pending" && (v.status = "rejected", v.reason = N);
          }
        )), v.status) {
          case "fulfilled":
            return v.value;
          case "rejected":
            throw v.reason;
        }
    }
    throw v;
  }
  function Y(v, N, C, R, k) {
    var P = typeof v;
    (P === "undefined" || P === "boolean") && (v = null);
    var al = !1;
    if (v === null) al = !0;
    else
      switch (P) {
        case "bigint":
        case "string":
        case "number":
          al = !0;
          break;
        case "object":
          switch (v.$$typeof) {
            case z:
            case B:
              al = !0;
              break;
            case p:
              return al = v._init, Y(
                al(v._payload),
                N,
                C,
                R,
                k
              );
          }
      }
    if (al)
      return k = k(v), al = R === "" ? "." + Ul(v, 0) : R, it(k) ? (C = "", al != null && (C = al.replace(Fa, "$&/") + "/"), Y(k, N, C, "", function(ta) {
        return ta;
      })) : k != null && (ct(k) && (k = bt(
        k,
        C + (k.key == null || v && v.key === k.key ? "" : ("" + k.key).replace(
          Fa,
          "$&/"
        ) + "/") + al
      )), N.push(k)), 1;
    al = 0;
    var M = R === "" ? "." : R + ":";
    if (it(v))
      for (var G = 0; G < v.length; G++)
        R = v[G], P = M + Ul(R, G), al += Y(
          R,
          N,
          C,
          P,
          k
        );
    else if (G = et(v), typeof G == "function")
      for (v = G.call(v), G = 0; !(R = v.next()).done; )
        R = R.value, P = M + Ul(R, G++), al += Y(
          R,
          N,
          C,
          P,
          k
        );
    else if (P === "object") {
      if (typeof v.then == "function")
        return Y(
          O(v),
          N,
          C,
          R,
          k
        );
      throw N = String(v), Error(
        "Objects are not valid as a React child (found: " + (N === "[object Object]" ? "object with keys {" + Object.keys(v).join(", ") + "}" : N) + "). If you meant to render a collection of children, use an array instead."
      );
    }
    return al;
  }
  function q(v, N, C) {
    if (v == null) return v;
    var R = [], k = 0;
    return Y(v, R, "", "", function(P) {
      return N.call(C, P, k++);
    }), R;
  }
  function ol(v) {
    if (v._status === -1) {
      var N = v._result, C = N();
      C.then(
        function(R) {
          (v._status === 0 || v._status === -1) && (v._status = 1, v._result = R, C.status === void 0 && (C.status = "fulfilled", C.value = R));
        },
        function(R) {
          (v._status === 0 || v._status === -1) && (v._status = 2, v._result = R, C.status === void 0 && (C.status = "rejected", C.reason = R));
        }
      ), v._status === -1 && (v._status = 0, v._result = C);
    }
    if (v._status === 1) return v._result.default;
    throw v._result;
  }
  var el = typeof reportError == "function" ? reportError : function(v) {
    if (typeof window == "object" && typeof window.ErrorEvent == "function") {
      var N = new window.ErrorEvent("error", {
        bubbles: !0,
        cancelable: !0,
        message: typeof v == "object" && v !== null && typeof v.message == "string" ? String(v.message) : String(v),
        error: v
      });
      if (!window.dispatchEvent(N)) return;
    } else if (typeof process == "object" && typeof process.emit == "function") {
      process.emit("uncaughtException", v);
      return;
    }
    console.error(v);
  };
  function Ct(v) {
    var N = tl.T, C = {};
    C.types = N !== null ? N.types : null, tl.T = C;
    try {
      var R = v(), k = tl.S;
      k !== null && k(C, R), typeof R == "object" && R !== null && typeof R.then == "function" && R.then(K, el);
    } catch (P) {
      el(P);
    } finally {
      N !== null && C.types !== null && (N.types = C.types), tl.T = N;
    }
  }
  function la(v) {
    var N = tl.T;
    if (N !== null) {
      var C = N.types;
      C === null ? N.types = [v] : C.indexOf(v) === -1 && C.push(v);
    } else Ct(la.bind(null, v));
  }
  var $a = {
    map: q,
    forEach: function(v, N, C) {
      q(
        v,
        function() {
          N.apply(this, arguments);
        },
        C
      );
    },
    count: function(v) {
      var N = 0;
      return q(v, function() {
        N++;
      }), N;
    },
    toArray: function(v) {
      return q(v, function(N) {
        return N;
      }) || [];
    },
    only: function(v) {
      if (!ct(v))
        throw Error(
          "React.Children.only expected to receive a single React element child."
        );
      return v;
    }
  };
  return X.Activity = E, X.Children = $a, X.Component = nt, X.Fragment = Q, X.Profiler = I, X.PureComponent = Bt, X.StrictMode = r, X.Suspense = bl, X.ViewTransition = H, X.__CLIENT_INTERNALS_DO_NOT_USE_OR_WARN_USERS_THEY_CANNOT_UPGRADE = tl, X.__COMPILER_RUNTIME = {
    __proto__: null,
    c: function(v) {
      return tl.H.useMemoCache(v);
    }
  }, X.addTransitionType = la, X.cache = function(v) {
    return function() {
      return v.apply(null, arguments);
    };
  }, X.cacheSignal = function() {
    return null;
  }, X.cloneElement = function(v, N, C) {
    if (v == null)
      throw Error(
        "The argument must be a React element, but you passed " + v + "."
      );
    var R = dl({}, v.props), k = v.key;
    if (N != null)
      for (P in N.key !== void 0 && (k = "" + N.key), N)
        !Xt.call(N, P) || P === "key" || P === "__self" || P === "__source" || P === "ref" && N.ref === void 0 || (R[P] = N[P]);
    var P = arguments.length - 2;
    if (P === 1) R.children = C;
    else if (1 < P) {
      for (var al = Array(P), M = 0; M < P; M++)
        al[M] = arguments[M + 2];
      R.children = al;
    }
    return Et(v.type, k, R);
  }, X.createContext = function(v) {
    return v = {
      $$typeof: Al,
      _currentValue: v,
      _currentValue2: v,
      _threadCount: 0,
      Provider: null,
      Consumer: null
    }, v.Provider = v, v.Consumer = {
      $$typeof: U,
      _context: v
    }, v;
  }, X.createElement = function(v, N, C) {
    var R, k = {}, P = null;
    if (N != null)
      for (R in N.key !== void 0 && (P = "" + N.key), N)
        Xt.call(N, R) && R !== "key" && R !== "__self" && R !== "__source" && (k[R] = N[R]);
    var al = arguments.length - 2;
    if (al === 1) k.children = C;
    else if (1 < al) {
      for (var M = Array(al), G = 0; G < al; G++)
        M[G] = arguments[G + 2];
      k.children = M;
    }
    if (v && v.defaultProps)
      for (R in al = v.defaultProps, al)
        k[R] === void 0 && (k[R] = al[R]);
    return Et(v, P, k);
  }, X.createRef = function() {
    return { current: null };
  }, X.forwardRef = function(v) {
    return { $$typeof: $l, render: v };
  }, X.isValidElement = ct, X.lazy = function(v) {
    return {
      $$typeof: p,
      _payload: { _status: -1, _result: v },
      _init: ol
    };
  }, X.memo = function(v, N) {
    return {
      $$typeof: pl,
      type: v,
      compare: N === void 0 ? null : N
    };
  }, X.startTransition = Ct, X.unstable_useCacheRefresh = function() {
    return tl.H.useCacheRefresh();
  }, X.use = function(v) {
    return tl.H.use(v);
  }, X.useActionState = function(v, N, C) {
    return tl.H.useActionState(v, N, C);
  }, X.useCallback = function(v, N) {
    return tl.H.useCallback(v, N);
  }, X.useContext = function(v) {
    return tl.H.useContext(v);
  }, X.useDebugValue = function() {
  }, X.useDeferredValue = function(v, N) {
    return tl.H.useDeferredValue(v, N);
  }, X.useEffect = function(v, N) {
    return tl.H.useEffect(v, N);
  }, X.useEffectEvent = function(v) {
    return tl.H.useEffectEvent(v);
  }, X.useId = function() {
    return tl.H.useId();
  }, X.useImperativeHandle = function(v, N, C) {
    return tl.H.useImperativeHandle(v, N, C);
  }, X.useInsertionEffect = function(v, N) {
    return tl.H.useInsertionEffect(v, N);
  }, X.useLayoutEffect = function(v, N) {
    return tl.H.useLayoutEffect(v, N);
  }, X.useMemo = function(v, N) {
    return tl.H.useMemo(v, N);
  }, X.useOptimistic = function(v, N) {
    return tl.H.useOptimistic(v, N);
  }, X.useReducer = function(v, N, C) {
    return tl.H.useReducer(v, N, C);
  }, X.useRef = function(v) {
    return tl.H.useRef(v);
  }, X.useState = function(v) {
    return tl.H.useState(v);
  }, X.useSyncExternalStore = function(v, N, C) {
    return tl.H.useSyncExternalStore(
      v,
      N,
      C
    );
  }, X.useTransition = function() {
    return tl.H.useTransition();
  }, X.version = "19.3.0", X;
}
var nm;
function js() {
  return nm || (nm = 1, bs.exports = oy()), bs.exports;
}
var Us = js(), zs = { exports: {} }, nn = {}, Ns = { exports: {} }, _s = {};
/**
 * @license React
 * scheduler.production.js
 *
 * Copyright (c) Meta Platforms, Inc. and affiliates.
 *
 * This source code is licensed under the MIT license found in the
 * LICENSE file in the root directory of this source tree.
 */
var im;
function vy() {
  return im || (im = 1, (function(z) {
    function B(O, Y) {
      var q = O.length;
      O.push(Y);
      l: for (; 0 < q; ) {
        var ol = q - 1 >>> 1, el = O[ol];
        if (0 < I(el, Y))
          O[ol] = Y, O[q] = el, q = ol;
        else break l;
      }
    }
    function Q(O) {
      return O.length === 0 ? null : O[0];
    }
    function r(O) {
      if (O.length === 0) return null;
      var Y = O[0], q = O.pop();
      if (q !== Y) {
        O[0] = q;
        l: for (var ol = 0, el = O.length, Ct = el >>> 1; ol < Ct; ) {
          var la = 2 * (ol + 1) - 1, $a = O[la], v = la + 1, N = O[v];
          if (0 > I($a, q))
            v < el && 0 > I(N, $a) ? (O[ol] = N, O[v] = q, ol = v) : (O[ol] = $a, O[la] = q, ol = la);
          else if (v < el && 0 > I(N, q))
            O[ol] = N, O[v] = q, ol = v;
          else break l;
        }
      }
      return Y;
    }
    function I(O, Y) {
      var q = O.sortIndex - Y.sortIndex;
      return q !== 0 ? q : O.id - Y.id;
    }
    if (z.unstable_now = void 0, typeof performance == "object" && typeof performance.now == "function") {
      var U = performance;
      z.unstable_now = function() {
        return U.now();
      };
    } else {
      var Al = Date, $l = Al.now();
      z.unstable_now = function() {
        return Al.now() - $l;
      };
    }
    var bl = [], pl = [], p = 1, E = null, H = 3, jl = !1, et = !1, Wl = !1, dl = !1, Tt = typeof setTimeout == "function" ? setTimeout : null, nt = typeof clearTimeout == "function" ? clearTimeout : null, wa = typeof setImmediate < "u" ? setImmediate : null;
    function Bt(O) {
      for (var Y = Q(pl); Y !== null; ) {
        if (Y.callback === null) r(pl);
        else if (Y.startTime <= O)
          r(pl), Y.sortIndex = Y.expirationTime, B(bl, Y);
        else break;
        Y = Q(pl);
      }
    }
    function Gt(O) {
      if (Wl = !1, Bt(O), !et)
        if (Q(bl) !== null)
          et = !0, it || (it = !0, ct());
        else {
          var Y = Q(pl);
          Y !== null && Ul(Gt, Y.startTime - O);
        }
    }
    var it = !1, K = -1, tl = 5, Xt = -1;
    function Et() {
      return dl ? !0 : !(z.unstable_now() - Xt < tl);
    }
    function bt() {
      if (dl = !1, it) {
        var O = z.unstable_now();
        Xt = O;
        var Y = !0;
        try {
          l: {
            et = !1, Wl && (Wl = !1, nt(K), K = -1), jl = !0;
            var q = H;
            try {
              t: {
                for (Bt(O), E = Q(bl); E !== null && !(E.expirationTime > O && Et()); ) {
                  var ol = E.callback;
                  if (typeof ol == "function") {
                    E.callback = null, H = E.priorityLevel;
                    var el = ol(
                      E.expirationTime <= O
                    );
                    if (O = z.unstable_now(), typeof el == "function") {
                      E.callback = el, Bt(O), Y = !0;
                      break t;
                    }
                    E === Q(bl) && r(bl), Bt(O);
                  } else r(bl);
                  E = Q(bl);
                }
                if (E !== null) Y = !0;
                else {
                  var Ct = Q(pl);
                  Ct !== null && Ul(
                    Gt,
                    Ct.startTime - O
                  ), Y = !1;
                }
              }
              break l;
            } finally {
              E = null, H = q, jl = !1;
            }
            Y = void 0;
          }
        } finally {
          Y ? ct() : it = !1;
        }
      }
    }
    var ct;
    if (typeof wa == "function")
      ct = function() {
        wa(bt);
      };
    else if (typeof MessageChannel < "u") {
      var ga = new MessageChannel(), Fa = ga.port2;
      ga.port1.onmessage = bt, ct = function() {
        Fa.postMessage(null);
      };
    } else
      ct = function() {
        Tt(bt, 0);
      };
    function Ul(O, Y) {
      K = Tt(function() {
        O(z.unstable_now());
      }, Y);
    }
    z.unstable_IdlePriority = 5, z.unstable_ImmediatePriority = 1, z.unstable_LowPriority = 4, z.unstable_NormalPriority = 3, z.unstable_Profiling = null, z.unstable_UserBlockingPriority = 2, z.unstable_cancelCallback = function(O) {
      O.callback = null;
    }, z.unstable_forceFrameRate = function(O) {
      0 > O || 125 < O ? console.error(
        "forceFrameRate takes a positive int between 0 and 125, forcing frame rates higher than 125 fps is not supported"
      ) : tl = 0 < O ? Math.floor(1e3 / O) : 5;
    }, z.unstable_getCurrentPriorityLevel = function() {
      return H;
    }, z.unstable_next = function(O) {
      switch (H) {
        case 1:
        case 2:
        case 3:
          var Y = 3;
          break;
        default:
          Y = H;
      }
      var q = H;
      H = Y;
      try {
        return O();
      } finally {
        H = q;
      }
    }, z.unstable_requestPaint = function() {
      dl = !0;
    }, z.unstable_runWithPriority = function(O, Y) {
      switch (O) {
        case 1:
        case 2:
        case 3:
        case 4:
        case 5:
          break;
        default:
          O = 3;
      }
      var q = H;
      H = O;
      try {
        return Y();
      } finally {
        H = q;
      }
    }, z.unstable_scheduleCallback = function(O, Y, q) {
      var ol = z.unstable_now();
      switch (typeof q == "object" && q !== null ? (q = q.delay, q = typeof q == "number" && 0 < q ? ol + q : ol) : q = ol, O) {
        case 1:
          var el = -1;
          break;
        case 2:
          el = 250;
          break;
        case 5:
          el = 1073741823;
          break;
        case 4:
          el = 1e4;
          break;
        default:
          el = 5e3;
      }
      return el = q + el, O = {
        id: p++,
        callback: Y,
        priorityLevel: O,
        startTime: q,
        expirationTime: el,
        sortIndex: -1
      }, q > ol ? (O.sortIndex = q, B(pl, O), Q(bl) === null && O === Q(pl) && (Wl ? (nt(K), K = -1) : Wl = !0, Ul(Gt, q - ol))) : (O.sortIndex = el, B(bl, O), et || jl || (et = !0, it || (it = !0, ct()))), O;
    }, z.unstable_shouldYield = Et, z.unstable_wrapCallback = function(O) {
      var Y = H;
      return function() {
        var q = H;
        H = Y;
        try {
          return O.apply(this, arguments);
        } finally {
          H = q;
        }
      };
    };
  })(_s)), _s;
}
var cm;
function dy() {
  return cm || (cm = 1, Ns.exports = vy()), Ns.exports;
}
var Os = { exports: {} }, Zl = {};
/**
 * @license React
 * react-dom.production.js
 *
 * Copyright (c) Meta Platforms, Inc. and affiliates.
 *
 * This source code is licensed under the MIT license found in the
 * LICENSE file in the root directory of this source tree.
 */
var fm;
function my() {
  if (fm) return Zl;
  fm = 1;
  var z = js();
  function B(p) {
    var E = "https://react.dev/errors/" + p;
    if (1 < arguments.length) {
      E += "?args[]=" + encodeURIComponent(arguments[1]);
      for (var H = 2; H < arguments.length; H++)
        E += "&args[]=" + encodeURIComponent(arguments[H]);
    }
    return "Minified React error #" + p + "; visit " + E + " for the full message or use the non-minified dev environment for full errors and additional helpful warnings.";
  }
  function Q() {
  }
  var r = {
    d: {
      f: Q,
      r: function() {
        throw Error(B(522));
      },
      D: Q,
      C: Q,
      L: Q,
      m: Q,
      X: Q,
      S: Q,
      M: Q
    },
    p: 0,
    findDOMNode: null
  }, I = Symbol.for("react.portal"), U = Symbol.for("react.recoverable"), Al = Symbol.for("react.optimistic_key");
  function $l(p, E, H) {
    var jl = 3 < arguments.length && arguments[3] !== void 0 ? arguments[3] : null;
    return {
      $$typeof: I,
      key: jl == null ? null : jl === Al ? Al : "" + jl,
      children: p,
      containerInfo: E,
      implementation: H
    };
  }
  var bl = z.__CLIENT_INTERNALS_DO_NOT_USE_OR_WARN_USERS_THEY_CANNOT_UPGRADE;
  function pl(p, E) {
    if (p === "font") return "";
    if (typeof E == "string")
      return E === "use-credentials" ? E : "";
  }
  return Zl.__DOM_INTERNALS_DO_NOT_USE_OR_WARN_USERS_THEY_CANNOT_UPGRADE = r, Zl.browser = function(p) {
    return { $$typeof: U, _reason: p };
  }, Zl.createPortal = function(p, E) {
    var H = 2 < arguments.length && arguments[2] !== void 0 ? arguments[2] : null;
    if (!E || E.nodeType !== 1 && E.nodeType !== 9 && E.nodeType !== 11)
      throw Error(B(299));
    return $l(p, E, null, H);
  }, Zl.flushSync = function(p) {
    var E = bl.T, H = r.p;
    try {
      if (bl.T = null, r.p = 2, p) return p();
    } finally {
      bl.T = E, r.p = H, r.d.f();
    }
  }, Zl.preconnect = function(p, E) {
    typeof p == "string" && (E ? (E = E.crossOrigin, E = typeof E == "string" ? E === "use-credentials" ? E : "" : void 0) : E = null, r.d.C(p, E));
  }, Zl.prefetchDNS = function(p) {
    typeof p == "string" && r.d.D(p);
  }, Zl.preinit = function(p, E) {
    if (typeof p == "string" && E && typeof E.as == "string") {
      var H = E.as, jl = pl(H, E.crossOrigin), et = typeof E.integrity == "string" ? E.integrity : void 0, Wl = typeof E.fetchPriority == "string" ? E.fetchPriority : void 0;
      H === "style" ? r.d.S(
        p,
        typeof E.precedence == "string" ? E.precedence : void 0,
        {
          crossOrigin: jl,
          integrity: et,
          fetchPriority: Wl
        }
      ) : H === "script" && r.d.X(p, {
        crossOrigin: jl,
        integrity: et,
        fetchPriority: Wl,
        nonce: typeof E.nonce == "string" ? E.nonce : void 0
      });
    }
  }, Zl.preinitModule = function(p, E) {
    if (typeof p == "string")
      if (typeof E == "object" && E !== null) {
        if (E.as == null || E.as === "script") {
          var H = pl(
            E.as,
            E.crossOrigin
          );
          r.d.M(p, {
            crossOrigin: H,
            integrity: typeof E.integrity == "string" ? E.integrity : void 0,
            nonce: typeof E.nonce == "string" ? E.nonce : void 0,
            fetchPriority: typeof E.fetchPriority == "string" ? E.fetchPriority : void 0
          });
        }
      } else E == null && r.d.M(p);
  }, Zl.preload = function(p, E) {
    if (typeof p == "string" && typeof E == "object" && E !== null && typeof E.as == "string") {
      var H = E.as, jl = pl(H, E.crossOrigin);
      r.d.L(p, H, {
        crossOrigin: jl,
        integrity: typeof E.integrity == "string" ? E.integrity : void 0,
        nonce: typeof E.nonce == "string" ? E.nonce : void 0,
        type: typeof E.type == "string" ? E.type : void 0,
        fetchPriority: typeof E.fetchPriority == "string" ? E.fetchPriority : void 0,
        referrerPolicy: typeof E.referrerPolicy == "string" ? E.referrerPolicy : void 0,
        imageSrcSet: typeof E.imageSrcSet == "string" ? E.imageSrcSet : void 0,
        imageSizes: typeof E.imageSizes == "string" ? E.imageSizes : void 0,
        media: typeof E.media == "string" ? E.media : void 0
      });
    }
  }, Zl.preloadModule = function(p, E) {
    if (typeof p == "string")
      if (E) {
        var H = pl(E.as, E.crossOrigin);
        r.d.m(p, {
          as: typeof E.as == "string" && E.as !== "script" ? E.as : void 0,
          crossOrigin: H,
          integrity: typeof E.integrity == "string" ? E.integrity : void 0,
          nonce: typeof E.nonce == "string" ? E.nonce : void 0,
          fetchPriority: typeof E.fetchPriority == "string" ? E.fetchPriority : void 0
        });
      } else r.d.m(p);
  }, Zl.requestFormReset = function(p) {
    r.d.r(p);
  }, Zl.unstable_batchedUpdates = function(p, E) {
    return p(E);
  }, Zl.useFormState = function(p, E, H) {
    return bl.H.useFormState(p, E, H);
  }, Zl.useFormStatus = function() {
    return bl.H.useHostTransitionStatus();
  }, Zl.version = "19.3.0", Zl;
}
var sm;
function hy() {
  if (sm) return Os.exports;
  sm = 1;
  function z() {
    if (!(typeof __REACT_DEVTOOLS_GLOBAL_HOOK__ > "u" || typeof __REACT_DEVTOOLS_GLOBAL_HOOK__.checkDCE != "function"))
      try {
        __REACT_DEVTOOLS_GLOBAL_HOOK__.checkDCE(z);
      } catch (B) {
        console.error(B);
      }
  }
  return z(), Os.exports = my(), Os.exports;
}
/**
 * @license React
 * react-dom-client.production.js
 *
 * Copyright (c) Meta Platforms, Inc. and affiliates.
 *
 * This source code is licensed under the MIT license found in the
 * LICENSE file in the root directory of this source tree.
 */
var om;
function ry() {
  if (om) return nn;
  om = 1;
  var z = dy(), B = js(), Q = hy();
  function r(l) {
    var t = "https://react.dev/errors/" + l;
    if (1 < arguments.length) {
      t += "?args[]=" + encodeURIComponent(arguments[1]);
      for (var a = 2; a < arguments.length; a++)
        t += "&args[]=" + encodeURIComponent(arguments[a]);
    }
    return "Minified React error #" + l + "; visit " + t + " for the full message or use the non-minified dev environment for full errors and additional helpful warnings.";
  }
  function I(l) {
    return !(!l || l.nodeType !== 1 && l.nodeType !== 9 && l.nodeType !== 11);
  }
  function U(l) {
    for (var t = l, a = t; a && !a.alternate; )
      t = a, (t.flags & 4098) !== 0 && (l = t.return), a = t.return;
    for (; t.return; ) t = t.return;
    return t.tag === 3 ? l : null;
  }
  function Al(l) {
    if (l.tag === 13) {
      var t = l.memoizedState;
      if (t === null && (l = l.alternate, l !== null && (t = l.memoizedState)), t !== null) return t.dehydrated;
    }
    return null;
  }
  function $l(l) {
    if (l.tag === 31) {
      var t = l.memoizedState;
      if (t === null && (l = l.alternate, l !== null && (t = l.memoizedState)), t !== null) return t.dehydrated;
    }
    return null;
  }
  function bl(l) {
    if (U(l) !== l)
      throw Error(r(188));
  }
  function pl(l) {
    var t = l.alternate;
    if (!t) {
      if (t = U(l), t === null) throw Error(r(188));
      return t !== l ? null : l;
    }
    for (var a = l, u = t; ; ) {
      var e = a.return;
      if (e === null) break;
      var n = e.alternate;
      if (n === null) {
        if (u = e.return, u !== null) {
          a = u;
          continue;
        }
        break;
      }
      if (e.child === n.child) {
        for (n = e.child; n; ) {
          if (n === a) return bl(e), l;
          if (n === u) return bl(e), t;
          n = n.sibling;
        }
        throw Error(r(188));
      }
      if (a.return !== u.return) a = e, u = n;
      else {
        for (var i = !1, c = e.child; c; ) {
          if (c === a) {
            i = !0, a = e, u = n;
            break;
          }
          if (c === u) {
            i = !0, u = e, a = n;
            break;
          }
          c = c.sibling;
        }
        if (!i) {
          for (c = n.child; c; ) {
            if (c === a) {
              i = !0, a = n, u = e;
              break;
            }
            if (c === u) {
              i = !0, u = n, a = e;
              break;
            }
            c = c.sibling;
          }
          if (!i) throw Error(r(189));
        }
      }
      if (a.alternate !== u) throw Error(r(190));
    }
    if (a.tag !== 3) throw Error(r(188));
    return a.stateNode.current === a ? l : t;
  }
  function p(l) {
    var t = l.tag;
    if (t === 5 || t === 26 || t === 27 || t === 6) return l;
    for (l = l.child; l !== null; ) {
      if (t = p(l), t !== null) return t;
      l = l.sibling;
    }
    return null;
  }
  function E(l, t, a, u, e, n) {
    for (; l !== null; ) {
      if ((l.tag === 5 || l.tag === 27 || l.tag === 6) && a(l, u, e, n) || (l.tag !== 22 || l.memoizedState === null) && (t || l.tag !== 5 && l.tag !== 27) && E(
        l.child,
        t,
        a,
        u,
        e,
        n
      ))
        return !0;
      l = l.sibling;
    }
    return !1;
  }
  function H(l) {
    for (l = l.return; l !== null; ) {
      if (l.tag === 3 || l.tag === 5 || l.tag === 27) return l;
      l = l.return;
    }
    return null;
  }
  function jl(l) {
    var t = !1;
    for (l = l.return; l !== null && (l.tag === 4 && (t = !0), !(l.tag === 3 || l.tag === 5 || l.tag === 27)); )
      l = l.return;
    return t;
  }
  function et(l) {
    var t = [null, null], a = H(l);
    return a === null || Wl(
      t,
      l,
      a.child,
      { foundSelf: !1 }
    ), t;
  }
  function Wl(l, t, a, u) {
    for (; a !== null; ) {
      if (a === t) u.foundSelf = !0;
      else if (a.tag === 5 || a.tag === 27 || a.tag === 6) {
        if (u.foundSelf) return l[1] = a, !0;
        l[0] = a;
      } else if ((a.tag !== 22 || a.memoizedState === null) && Wl(
        l,
        t,
        a.child,
        u
      ))
        return !0;
      a = a.sibling;
    }
    return !1;
  }
  function dl(l) {
    switch (l.tag) {
      case 5:
      case 27:
      case 6:
        return l.stateNode;
      case 3:
        return l.stateNode.containerInfo;
      default:
        throw Error(r(559));
    }
  }
  var Tt = null, nt = null;
  function wa(l, t, a) {
    return l === a ? !0 : l === t ? (Tt = l, !0) : !1;
  }
  function Bt(l, t, a) {
    return l === a ? (nt = l, !1) : l === t ? (nt !== null && (Tt = l), !0) : !1;
  }
  function Gt(l) {
    if (l === null) return null;
    do
      l = l === null ? null : l.return;
    while (l && l.tag !== 5 && l.tag !== 27 && l.tag !== 3);
    return l || null;
  }
  function it(l, t, a) {
    for (var u = 0, e = l; e; e = a(e)) u++;
    e = 0;
    for (var n = t; n; n = a(n)) e++;
    for (; 0 < u - e; ) l = a(l), u--;
    for (; 0 < e - u; ) t = a(t), e--;
    for (; u--; ) {
      if (l === t || t !== null && l === t.alternate)
        return l;
      l = a(l), t = a(t);
    }
    return null;
  }
  var K = Object.assign, tl = Symbol.for("react.element"), Xt = Symbol.for("react.transitional.element"), Et = Symbol.for("react.portal"), bt = Symbol.for("react.fragment"), ct = Symbol.for("react.strict_mode"), ga = Symbol.for("react.profiler"), Fa = Symbol.for("react.consumer"), Ul = Symbol.for("react.context"), O = Symbol.for("react.forward_ref"), Y = Symbol.for("react.suspense"), q = Symbol.for("react.suspense_list"), ol = Symbol.for("react.memo"), el = Symbol.for("react.lazy"), Ct = Symbol.for("react.activity"), la = Symbol.for("react.legacy_hidden"), $a = Symbol.for("react.memo_cache_sentinel"), v = Symbol.for("react.view_transition"), N = Symbol.for("react.recoverable"), C = Symbol.iterator;
  function R(l) {
    return l === null || typeof l != "object" ? null : (l = C && l[C] || l["@@iterator"], typeof l == "function" ? l : null);
  }
  var k = Symbol.for("react.client.reference");
  function P(l) {
    if (l == null) return null;
    if (typeof l == "function")
      return l.$$typeof === k ? null : l.displayName || l.name || null;
    if (typeof l == "string") return l;
    switch (l) {
      case bt:
        return "Fragment";
      case ga:
        return "Profiler";
      case ct:
        return "StrictMode";
      case Y:
        return "Suspense";
      case q:
        return "SuspenseList";
      case Ct:
        return "Activity";
      case v:
        return "ViewTransition";
    }
    if (typeof l == "object")
      switch (l.$$typeof) {
        case Et:
          return "Portal";
        case Ul:
          return l.displayName || "Context";
        case Fa:
          return (l._context.displayName || "Context") + ".Consumer";
        case O:
          var t = l.render;
          return l = l.displayName, l || (l = t.displayName || t.name || "", l = l !== "" ? "ForwardRef(" + l + ")" : "ForwardRef"), l;
        case ol:
          return t = l.displayName || null, t !== null ? t : P(l.type) || "Memo";
        case el:
          t = l._payload, l = l._init;
          try {
            return P(l(t));
          } catch {
          }
      }
    return null;
  }
  var al = Array.isArray, M = B.__CLIENT_INTERNALS_DO_NOT_USE_OR_WARN_USERS_THEY_CANNOT_UPGRADE, G = Q.__DOM_INTERNALS_DO_NOT_USE_OR_WARN_USERS_THEY_CANNOT_UPGRADE, ta = {
    pending: !1,
    data: null,
    method: null,
    action: null
  }, Bi = [], Tu = -1;
  function Qt(l) {
    return { current: l };
  }
  function Yl(l) {
    0 > Tu || (l.current = Bi[Tu], Bi[Tu] = null, Tu--);
  }
  function ml(l, t) {
    Tu++, Bi[Tu] = l.current, l.current = t;
  }
  var Vt = Qt(null), oe = Qt(null), Sa = Qt(null), cn = Qt(null);
  function fn(l, t) {
    switch (ml(Sa, t), ml(oe, l), ml(Vt, null), t.nodeType) {
      case 9:
      case 11:
        l = (l = t.documentElement) && (l = l.namespaceURI) ? vd(l) : 0;
        break;
      default:
        if (l = t.tagName, t = t.namespaceURI)
          t = vd(t), l = dd(t, l);
        else
          switch (l) {
            case "svg":
              l = 1;
              break;
            case "math":
              l = 2;
              break;
            default:
              l = 0;
          }
    }
    Yl(Vt), ml(Vt, l);
  }
  function Eu() {
    Yl(Vt), Yl(oe), Yl(Sa);
  }
  function Gi(l) {
    var t = l.memoizedState;
    t !== null && (ce._currentValue = t.memoizedState, ml(cn, l)), t = Vt.current;
    var a = dd(t, l.type);
    t !== a && (ml(oe, l), ml(Vt, a));
  }
  function sn(l) {
    oe.current === l && (Yl(Vt), Yl(oe)), cn.current === l && (Yl(cn), ce._currentValue = ta);
  }
  var Xi, Cs;
  function Ta(l) {
    if (Xi === void 0)
      try {
        throw Error();
      } catch (a) {
        var t = a.stack.trim().match(/\n( *(at )?)/);
        Xi = t && t[1] || "", Cs = -1 < a.stack.indexOf(`
    at`) ? " (<anonymous>)" : -1 < a.stack.indexOf("@") ? "@unknown:0:0" : "";
      }
    return `
` + Xi + l + Cs;
  }
  var Qi = !1;
  function Vi(l, t) {
    if (!l || Qi) return "";
    Qi = !0;
    var a = Error.prepareStackTrace;
    Error.prepareStackTrace = void 0;
    try {
      var u = {
        DetermineComponentFrameRoot: function() {
          try {
            if (t) {
              var b = function() {
                throw Error();
              };
              if (Object.defineProperty(b.prototype, "props", {
                set: function() {
                  throw Error();
                }
              }), typeof Reflect == "object" && Reflect.construct) {
                try {
                  Reflect.construct(b, []);
                } catch (_) {
                  var d = _;
                }
                Reflect.construct(l, [], b);
              } else {
                try {
                  b.call();
                } catch (_) {
                  d = _;
                }
                b = !1;
                try {
                  var g = Object.getOwnPropertyDescriptor(
                    l.prototype,
                    "props"
                  );
                  Object.defineProperty(l.prototype, "props", {
                    configurable: !0,
                    set: function() {
                      throw Error();
                    }
                  }), b = !0, new l();
                } finally {
                  b && (g !== void 0 ? Object.defineProperty(l.prototype, "props", g) : delete l.prototype.props);
                }
              }
            } else {
              try {
                throw Error();
              } catch (_) {
                d = _;
              }
              (b = l()) && typeof b.catch == "function" && b.catch(function() {
              });
            }
          } catch (_) {
            if (_ && d && typeof _.stack == "string")
              return [_.stack, d.stack];
          }
          return [null, null];
        }
      };
      u.DetermineComponentFrameRoot.displayName = "DetermineComponentFrameRoot";
      var e = Object.getOwnPropertyDescriptor(
        u.DetermineComponentFrameRoot,
        "name"
      );
      e && e.configurable && Object.defineProperty(
        u.DetermineComponentFrameRoot,
        "name",
        { value: "DetermineComponentFrameRoot" }
      );
      var n = u.DetermineComponentFrameRoot(), i = n[0], c = n[1];
      if (i && c) {
        var f = i.split(`
`), h = c.split(`
`);
        for (e = u = 0; u < f.length && !f[u].includes("DetermineComponentFrameRoot"); )
          u++;
        for (; e < h.length && !h[e].includes(
          "DetermineComponentFrameRoot"
        ); )
          e++;
        if (u === f.length || e === h.length)
          for (u = f.length - 1, e = h.length - 1; 1 <= u && 0 <= e && f[u] !== h[e]; )
            e--;
        for (; 1 <= u && 0 <= e; u--, e--)
          if (f[u] !== h[e]) {
            if (u !== 1 || e !== 1)
              do
                if (u--, e--, 0 > e || f[u] !== h[e]) {
                  var S = `
` + f[u].replace(" at new ", " at ");
                  return l.displayName && S.includes("<anonymous>") && (S = S.replace("<anonymous>", l.displayName)), S;
                }
              while (1 <= u && 0 <= e);
            break;
          }
      }
    } finally {
      Qi = !1, Error.prepareStackTrace = a;
    }
    return (a = l ? l.displayName || l.name : "") ? Ta(a) : "";
  }
  function dm(l, t) {
    switch (l.tag) {
      case 26:
      case 27:
      case 5:
        return Ta(l.type);
      case 16:
        return Ta("Lazy");
      case 13:
        return l.child !== t && t !== null ? Ta("Suspense Fallback") : Ta("Suspense");
      case 19:
        return Ta("SuspenseList");
      case 0:
      case 15:
        return Vi(l.type, !1);
      case 11:
        return Vi(l.type.render, !1);
      case 1:
        return Vi(l.type, !0);
      case 31:
        return Ta("Activity");
      case 30:
        return Ta("ViewTransition");
      default:
        return "";
    }
  }
  function Rs(l) {
    try {
      var t = "", a = null;
      do
        t += dm(l, a), a = l, l = l.return;
      while (l);
      return t;
    } catch (u) {
      return `
Error generating stack: ` + u.message + `
` + u.stack;
    }
  }
  var Zi = Object.prototype.hasOwnProperty, Li = z.unstable_scheduleCallback, Ki = z.unstable_cancelCallback, mm = z.unstable_shouldYield, hm = z.unstable_requestPaint, ft = z.unstable_now, rm = z.unstable_getCurrentPriorityLevel, xs = z.unstable_ImmediatePriority, Hs = z.unstable_UserBlockingPriority, on = z.unstable_NormalPriority, ym = z.unstable_LowPriority, ps = z.unstable_IdlePriority, gm = z.log, Sm = z.unstable_setDisableYieldValue, ve = null, st = null;
  function Ea(l) {
    if (typeof gm == "function" && Sm(l), st && typeof st.setStrictMode == "function")
      try {
        st.setStrictMode(ve, l);
      } catch {
      }
  }
  var ot = Math.clz32 ? Math.clz32 : bm, Tm = Math.log, Em = Math.LN2;
  function bm(l) {
    return l >>>= 0, l === 0 ? 32 : 31 - (Tm(l) / Em | 0) | 0;
  }
  var vn = 256, dn = 262144, mn = 4194304;
  function Wa(l) {
    var t = l & 42;
    if (t !== 0) return t;
    switch (l & -l) {
      case 1:
        return 1;
      case 2:
        return 2;
      case 4:
        return 4;
      case 8:
        return 8;
      case 16:
        return 16;
      case 32:
        return 32;
      case 64:
        return 64;
      case 128:
        return 128;
      case 256:
      case 512:
      case 1024:
      case 2048:
      case 4096:
      case 8192:
      case 16384:
      case 32768:
      case 65536:
      case 131072:
        return l & -l;
      case 262144:
      case 524288:
      case 1048576:
      case 2097152:
        return l & 3932160;
      case 4194304:
      case 8388608:
      case 16777216:
      case 33554432:
        return l & 62914560;
      case 67108864:
        return 67108864;
      case 134217728:
        return 134217728;
      case 268435456:
        return 268435456;
      case 536870912:
        return 536870912;
      case 1073741824:
        return 0;
      default:
        return l;
    }
  }
  function hn(l, t, a) {
    var u = l.pendingLanes;
    if (u === 0) return 0;
    var e = 0, n = l.suspendedLanes, i = l.pingedLanes;
    l = l.warmLanes;
    var c = u & 134217727;
    return c !== 0 ? (u = c & ~n, u !== 0 ? e = Wa(u) : (i &= c, i !== 0 ? e = Wa(i) : a || (a = c & ~l, a !== 0 && (e = Wa(a))))) : (c = u & ~n, c !== 0 ? e = Wa(c) : i !== 0 ? e = Wa(i) : a || (a = u & ~l, a !== 0 && (e = Wa(a)))), e === 0 ? 0 : t !== 0 && t !== e && (t & n) === 0 && (n = e & -e, a = t & -t, n >= a || n === 32 && (a & 4194048) !== 0) ? t : e;
  }
  function de(l, t) {
    return (l.pendingLanes & ~(l.suspendedLanes & ~l.pingedLanes) & t) === 0;
  }
  function Ys(l, t) {
    (t & 8) !== 0 && (t |= t & 32);
    var a = l.entangledLanes;
    if (a !== 0)
      for (l = l.entanglements, a &= t; 0 < a; ) {
        var u = 31 - ot(a), e = 1 << u;
        t |= l[u], a &= ~e;
      }
    return t;
  }
  function zm(l, t) {
    switch (l) {
      case 1:
      case 2:
      case 4:
      case 8:
      case 64:
        return t + 250;
      case 16:
      case 32:
      case 128:
      case 256:
      case 512:
      case 1024:
      case 2048:
      case 4096:
      case 8192:
      case 16384:
      case 32768:
      case 65536:
      case 131072:
      case 262144:
      case 524288:
      case 1048576:
      case 2097152:
        return t + 5e3;
      case 4194304:
      case 8388608:
      case 16777216:
      case 33554432:
        return -1;
      case 67108864:
      case 134217728:
      case 268435456:
      case 536870912:
      case 1073741824:
        return -1;
      default:
        return -1;
    }
  }
  function qs() {
    var l = mn;
    return mn <<= 1, (mn & 62914560) === 0 && (mn = 4194304), l;
  }
  function Ji(l) {
    for (var t = [], a = 0; 31 > a; a++) t.push(l);
    return t;
  }
  function me(l, t) {
    l.pendingLanes |= t, t !== 268435456 && (l.suspendedLanes = 0, l.pingedLanes = 0, l.warmLanes = 0);
  }
  function Nm(l, t, a, u, e, n) {
    var i = l.pendingLanes;
    l.pendingLanes = a, l.suspendedLanes = 0, l.pingedLanes = 0, l.warmLanes = 0, l.expiredLanes &= a, l.entangledLanes &= a, l.errorRecoveryDisabledLanes &= a, l.shellSuspendCounter = 0;
    var c = l.entanglements, f = l.expirationTimes, h = l.hiddenUpdates;
    for (a = i & ~a; 0 < a; ) {
      var S = 31 - ot(a), b = 1 << S;
      c[S] = 0, f[S] = -1;
      var d = h[S];
      if (d !== null)
        for (h[S] = null, S = 0; S < d.length; S++) {
          var g = d[S];
          g !== null && (g.lane &= -536870913);
        }
      a &= ~b;
    }
    u !== 0 && Bs(l, u, 0), n !== 0 && e === 0 && l.tag !== 0 && (l.suspendedLanes |= n & ~(i & ~t));
  }
  function Bs(l, t, a) {
    l.pendingLanes |= t, l.suspendedLanes &= ~t;
    var u = 31 - ot(t);
    l.entangledLanes |= t, l.entanglements[u] = l.entanglements[u] | 1073741824 | a & 261930;
  }
  function Gs(l, t) {
    var a = l.entangledLanes |= t;
    for (l = l.entanglements; a; ) {
      var u = 31 - ot(a), e = 1 << u;
      e & t | l[u] & t && (l[u] |= t), a &= ~e;
    }
  }
  function Xs(l, t) {
    var a = t & -t;
    return a = (a & 42) !== 0 ? 1 : wi(a), (a & (l.suspendedLanes | t)) !== 0 ? 0 : a;
  }
  function wi(l) {
    switch (l) {
      case 2:
        l = 1;
        break;
      case 8:
        l = 4;
        break;
      case 32:
        l = 16;
        break;
      case 256:
      case 512:
      case 1024:
      case 2048:
      case 4096:
      case 8192:
      case 16384:
      case 32768:
      case 65536:
      case 131072:
      case 262144:
      case 524288:
      case 1048576:
      case 2097152:
      case 4194304:
      case 8388608:
      case 16777216:
      case 33554432:
        l = 128;
        break;
      case 268435456:
        l = 134217728;
        break;
      default:
        l = 0;
    }
    return l;
  }
  function Fi(l) {
    return l &= -l, 2 < l ? 8 < l ? (l & 134217727) !== 0 ? 32 : 268435456 : 8 : 2;
  }
  function Qs() {
    var l = G.p;
    return l !== 0 ? l : (l = window.event, l === void 0 ? 32 : $d(l.type));
  }
  function Vs(l, t) {
    var a = G.p;
    try {
      return G.p = l, t();
    } finally {
      G.p = a;
    }
  }
  var aa = Math.random().toString(36).slice(2), ql = "__reactFiber$" + aa, kl = "__reactProps$" + aa, bu = "__reactContainer$" + aa, Zs = "__reactEvents$" + aa, _m = "__reactListeners$" + aa, Om = "__reactHandles$" + aa, Ls = "__reactResources$" + aa, he = "__reactMarker$" + aa, rn = "__reactLoad$" + aa;
  function yn(l) {
    delete l[ql], delete l[kl], delete l[_m], delete l[Om];
  }
  function ka(l) {
    var t;
    if (t = l[ql]) return t;
    for (var a = l.parentNode; a; ) {
      if (t = a[bu] || a[ql]) {
        if (a = t.alternate, t.child !== null || a !== null && a.child !== null)
          for (l = jd(l); l !== null; ) {
            if (a = l[ql]) return a;
            l = jd(l);
          }
        return t;
      }
      l = a, a = l.parentNode;
    }
    return null;
  }
  function zu(l) {
    if (l = l[ql] || l[bu]) {
      var t = l.tag;
      if (t === 5 || t === 6 || t === 13 || t === 31 || t === 26 || t === 27 || t === 3)
        return l;
    }
    return null;
  }
  function re(l) {
    var t = l.tag;
    if (t === 5 || t === 26 || t === 27 || t === 6) return l.stateNode;
    throw Error(r(33));
  }
  function Nu(l) {
    var t = l[Ls];
    return t || (t = l[Ls] = { hoistableStyles: /* @__PURE__ */ new Map(), hoistableScripts: /* @__PURE__ */ new Map() }), t;
  }
  function Cl(l) {
    l[he] = !0;
  }
  function Ks(l) {
    l[rn] = void 0;
  }
  var Js = /* @__PURE__ */ new Set(), ws = {};
  function Ia(l, t) {
    _u(l, t), _u(l + "Capture", t);
  }
  function _u(l, t) {
    for (ws[l] = t, l = 0; l < t.length; l++)
      Js.add(t[l]);
  }
  var Am = RegExp(
    "^[:A-Z_a-z\\u00C0-\\u00D6\\u00D8-\\u00F6\\u00F8-\\u02FF\\u0370-\\u037D\\u037F-\\u1FFF\\u200C-\\u200D\\u2070-\\u218F\\u2C00-\\u2FEF\\u3001-\\uD7FF\\uF900-\\uFDCF\\uFDF0-\\uFFFD][:A-Z_a-z\\u00C0-\\u00D6\\u00D8-\\u00F6\\u00F8-\\u02FF\\u0370-\\u037D\\u037F-\\u1FFF\\u200C-\\u200D\\u2070-\\u218F\\u2C00-\\u2FEF\\u3001-\\uD7FF\\uF900-\\uFDCF\\uFDF0-\\uFFFD\\-.0-9\\u00B7\\u0300-\\u036F\\u203F-\\u2040]*$"
  ), Fs = {}, $s = {};
  function Dm(l) {
    return Zi.call($s, l) ? !0 : Zi.call(Fs, l) ? !1 : Am.test(l) ? $s[l] = !0 : (Fs[l] = !0, !1);
  }
  var ll = !1;
  function Ws() {
    var l = ll;
    return ll = !1, l;
  }
  function gn(l, t, a) {
    if (Dm(t))
      if (a === null) l.removeAttribute(t);
      else {
        switch (typeof a) {
          case "undefined":
          case "function":
          case "symbol":
            l.removeAttribute(t);
            return;
          case "boolean":
            var u = t.toLowerCase().slice(0, 5);
            if (u !== "data-" && u !== "aria-") {
              l.removeAttribute(t);
              return;
            }
        }
        l.setAttribute(t, a);
      }
  }
  function Sn(l, t, a) {
    if (a === null) l.removeAttribute(t);
    else {
      switch (typeof a) {
        case "undefined":
        case "function":
        case "symbol":
        case "boolean":
          l.removeAttribute(t);
          return;
      }
      l.setAttribute(t, a);
    }
  }
  function ua(l, t, a, u) {
    if (u === null) l.removeAttribute(a);
    else {
      switch (typeof u) {
        case "undefined":
        case "function":
        case "symbol":
        case "boolean":
          l.removeAttribute(a);
          return;
      }
      l.setAttributeNS(t, a, u);
    }
  }
  function vt(l) {
    switch (typeof l) {
      case "bigint":
      case "boolean":
      case "number":
      case "string":
      case "undefined":
        return l;
      case "object":
        return l;
      default:
        return "";
    }
  }
  function ks(l) {
    var t = l.type;
    return (l = l.nodeName) && l.toLowerCase() === "input" && (t === "checkbox" || t === "radio");
  }
  function Mm(l, t, a) {
    var u = Object.getOwnPropertyDescriptor(
      l.constructor.prototype,
      t
    );
    if (!l.hasOwnProperty(t) && typeof u < "u" && typeof u.get == "function" && typeof u.set == "function") {
      var e = u.get, n = u.set;
      return Object.defineProperty(l, t, {
        configurable: !0,
        get: function() {
          return e.call(this);
        },
        set: function(i) {
          a = "" + i, n.call(this, i);
        }
      }), Object.defineProperty(l, t, {
        enumerable: u.enumerable
      }), {
        getValue: function() {
          return a;
        },
        setValue: function(i) {
          a = "" + i;
        },
        stopTracking: function() {
          l._valueTracker = null, delete l[t];
        }
      };
    }
  }
  function $i(l) {
    if (!l._valueTracker) {
      var t = ks(l) ? "checked" : "value";
      l._valueTracker = Mm(
        l,
        t,
        "" + l[t]
      );
    }
  }
  function Is(l) {
    if (!l) return !1;
    var t = l._valueTracker;
    if (!t) return !0;
    var a = t.getValue(), u = "";
    return l && (u = ks(l) ? l.checked ? "true" : "false" : l.value), l = u, l !== a ? (t.setValue(l), !0) : !1;
  }
  var jm = /[\n"\\]/g;
  function zt(l) {
    return l.replace(
      jm,
      function(t) {
        return "\\" + t.charCodeAt(0).toString(16) + " ";
      }
    );
  }
  function Wi(l, t, a, u, e, n, i, c) {
    l.name = "", i != null && typeof i != "function" && typeof i != "symbol" && typeof i != "boolean" ? l.type = i : l.removeAttribute("type"), t != null ? i === "number" ? (t === 0 && l.value === "" || l.value != t) && (l.value = "" + vt(t)) : l.value !== "" + vt(t) && (l.value = "" + vt(t)) : i !== "submit" && i !== "reset" || l.removeAttribute("value"), t != null ? i === "number" && l.value == t ? ki(l, vt(l.value)) : ki(l, vt(t)) : a != null ? ki(l, vt(a)) : u != null && l.removeAttribute("value"), e == null && n != null && (l.defaultChecked = !!n), e != null && (l.checked = e && typeof e != "function" && typeof e != "symbol"), c != null && typeof c != "function" && typeof c != "symbol" && typeof c != "boolean" ? l.name = "" + vt(c) : l.removeAttribute("name");
  }
  function Ps(l, t, a, u, e, n, i, c) {
    if (n != null && typeof n != "function" && typeof n != "symbol" && typeof n != "boolean" && (l.type = n), t != null || a != null) {
      if (!(n !== "submit" && n !== "reset" || t != null)) {
        $i(l);
        return;
      }
      a = a != null ? "" + vt(a) : "", t = t != null ? "" + vt(t) : a, c || t === l.value || (l.value = t), l.defaultValue = t;
    }
    u = u ?? e, u = typeof u != "function" && typeof u != "symbol" && !!u, l.checked = c ? l.checked : !!u, l.defaultChecked = !!u, i != null && typeof i != "function" && typeof i != "symbol" && typeof i != "boolean" && (l.name = i), $i(l);
  }
  function ki(l, t) {
    l.defaultValue !== "" + t && (l.defaultValue = "" + t);
  }
  function Ou(l, t, a, u) {
    if (l = l.options, t) {
      t = {};
      for (var e = 0; e < a.length; e++)
        t["$" + a[e]] = !0;
      for (a = 0; a < l.length; a++)
        e = t.hasOwnProperty("$" + l[a].value), l[a].selected !== e && (l[a].selected = e), e && u && (l[a].defaultSelected = !0);
    } else {
      for (a = "" + vt(a), t = null, e = 0; e < l.length; e++) {
        if (l[e].value === a) {
          l[e].selected = !0, u && (l[e].defaultSelected = !0);
          return;
        }
        t !== null || l[e].disabled || (t = l[e]);
      }
      t !== null && (t.selected = !0);
    }
  }
  function lo(l, t, a) {
    if (t != null && (t = "" + vt(t), t !== l.value && (l.value = t), a == null)) {
      l.defaultValue !== t && (l.defaultValue = t);
      return;
    }
    l.defaultValue = a != null ? "" + vt(a) : "";
  }
  function to(l, t, a, u) {
    if (t == null) {
      if (u != null) {
        if (a != null) throw Error(r(92));
        if (al(u)) {
          if (1 < u.length) throw Error(r(93));
          u = u[0];
        }
        a = u;
      }
      a == null && (a = ""), t = a;
    }
    a = vt(t), l.defaultValue = a, u = l.textContent, u === a && u !== "" && u !== null && (l.value = u), $i(l);
  }
  function Au(l, t) {
    if (t) {
      var a = l.firstChild;
      if (a && a === l.lastChild && a.nodeType === 3) {
        a.nodeValue = t;
        return;
      }
    }
    l.textContent = t;
  }
  var Um = new Set(
    "animationIterationCount aspectRatio borderImageOutset borderImageSlice borderImageWidth boxFlex boxFlexGroup boxOrdinalGroup columnCount columns flex flexGrow flexPositive flexShrink flexNegative flexOrder gridArea gridRow gridRowEnd gridRowSpan gridRowStart gridColumn gridColumnEnd gridColumnSpan gridColumnStart fontWeight lineClamp lineHeight opacity order orphans scale tabSize widows zIndex zoom fillOpacity floodOpacity stopOpacity strokeDasharray strokeDashoffset strokeMiterlimit strokeOpacity strokeWidth MozAnimationIterationCount MozBoxFlex MozBoxFlexGroup MozLineClamp msAnimationIterationCount msFlex msZoom msFlexGrow msFlexNegative msFlexOrder msFlexPositive msFlexShrink msGridColumn msGridColumnSpan msGridRow msGridRowSpan WebkitAnimationIterationCount WebkitBoxFlex WebKitBoxFlexGroup WebkitBoxOrdinalGroup WebkitColumnCount WebkitColumns WebkitFlex WebkitFlexGrow WebkitFlexPositive WebkitFlexShrink WebkitLineClamp".split(
      " "
    )
  );
  function ao(l, t, a) {
    var u = t.indexOf("--") === 0;
    a == null || typeof a == "boolean" || a === "" ? u ? l.setProperty(t, "") : t === "float" ? l.cssFloat = "" : l[t] = "" : u ? l.setProperty(t, a) : typeof a != "number" || a === 0 || Um.has(t) ? t === "float" ? l.cssFloat = a : l[t] = ("" + a).trim() : l[t] = a + "px";
  }
  function uo(l, t, a) {
    if (t != null && typeof t != "object")
      throw Error(r(62));
    if (l = l.style, a != null) {
      for (var u in a)
        !a.hasOwnProperty(u) || t != null && t.hasOwnProperty(u) || (u.indexOf("--") === 0 ? l.setProperty(u, "") : u === "float" ? l.cssFloat = "" : l[u] = "", ll = !0);
      for (var e in t)
        u = t[e], t.hasOwnProperty(e) && a[e] !== u && (ao(l, e, u), ll = !0);
    } else
      for (var n in t)
        t.hasOwnProperty(n) && ao(l, n, t[n]);
  }
  function Ii(l) {
    if (l.indexOf("-") === -1) return !1;
    switch (l) {
      case "annotation-xml":
      case "color-profile":
      case "font-face":
      case "font-face-src":
      case "font-face-uri":
      case "font-face-format":
      case "font-face-name":
      case "missing-glyph":
        return !1;
      default:
        return !0;
    }
  }
  var Cm = /* @__PURE__ */ new Map([
    ["acceptCharset", "accept-charset"],
    ["htmlFor", "for"],
    ["httpEquiv", "http-equiv"],
    ["crossOrigin", "crossorigin"],
    ["accentHeight", "accent-height"],
    ["alignmentBaseline", "alignment-baseline"],
    ["arabicForm", "arabic-form"],
    ["baselineShift", "baseline-shift"],
    ["capHeight", "cap-height"],
    ["clipPath", "clip-path"],
    ["clipRule", "clip-rule"],
    ["colorInterpolation", "color-interpolation"],
    ["colorInterpolationFilters", "color-interpolation-filters"],
    ["colorProfile", "color-profile"],
    ["colorRendering", "color-rendering"],
    ["dominantBaseline", "dominant-baseline"],
    ["enableBackground", "enable-background"],
    ["fillOpacity", "fill-opacity"],
    ["fillRule", "fill-rule"],
    ["floodColor", "flood-color"],
    ["floodOpacity", "flood-opacity"],
    ["fontFamily", "font-family"],
    ["fontSize", "font-size"],
    ["fontSizeAdjust", "font-size-adjust"],
    ["fontStretch", "font-stretch"],
    ["fontStyle", "font-style"],
    ["fontVariant", "font-variant"],
    ["fontWeight", "font-weight"],
    ["glyphName", "glyph-name"],
    ["glyphOrientationHorizontal", "glyph-orientation-horizontal"],
    ["glyphOrientationVertical", "glyph-orientation-vertical"],
    ["horizAdvX", "horiz-adv-x"],
    ["horizOriginX", "horiz-origin-x"],
    ["imageRendering", "image-rendering"],
    ["letterSpacing", "letter-spacing"],
    ["lightingColor", "lighting-color"],
    ["markerEnd", "marker-end"],
    ["markerMid", "marker-mid"],
    ["markerStart", "marker-start"],
    ["maskType", "mask-type"],
    ["overlinePosition", "overline-position"],
    ["overlineThickness", "overline-thickness"],
    ["paintOrder", "paint-order"],
    ["panose-1", "panose-1"],
    ["pointerEvents", "pointer-events"],
    ["renderingIntent", "rendering-intent"],
    ["shapeRendering", "shape-rendering"],
    ["stopColor", "stop-color"],
    ["stopOpacity", "stop-opacity"],
    ["strikethroughPosition", "strikethrough-position"],
    ["strikethroughThickness", "strikethrough-thickness"],
    ["strokeDasharray", "stroke-dasharray"],
    ["strokeDashoffset", "stroke-dashoffset"],
    ["strokeLinecap", "stroke-linecap"],
    ["strokeLinejoin", "stroke-linejoin"],
    ["strokeMiterlimit", "stroke-miterlimit"],
    ["strokeOpacity", "stroke-opacity"],
    ["strokeWidth", "stroke-width"],
    ["textAnchor", "text-anchor"],
    ["textDecoration", "text-decoration"],
    ["textRendering", "text-rendering"],
    ["transformOrigin", "transform-origin"],
    ["underlinePosition", "underline-position"],
    ["underlineThickness", "underline-thickness"],
    ["unicodeBidi", "unicode-bidi"],
    ["unicodeRange", "unicode-range"],
    ["unitsPerEm", "units-per-em"],
    ["vAlphabetic", "v-alphabetic"],
    ["vHanging", "v-hanging"],
    ["vIdeographic", "v-ideographic"],
    ["vMathematical", "v-mathematical"],
    ["vectorEffect", "vector-effect"],
    ["vertAdvY", "vert-adv-y"],
    ["vertOriginX", "vert-origin-x"],
    ["vertOriginY", "vert-origin-y"],
    ["wordSpacing", "word-spacing"],
    ["writingMode", "writing-mode"],
    ["xmlnsXlink", "xmlns:xlink"],
    ["xHeight", "x-height"]
  ]), Rm = /^[\u0000-\u001F ]*j[\r\n\t]*a[\r\n\t]*v[\r\n\t]*a[\r\n\t]*s[\r\n\t]*c[\r\n\t]*r[\r\n\t]*i[\r\n\t]*p[\r\n\t]*t[\r\n\t]*:/i;
  function Tn(l) {
    return Rm.test("" + l) ? "javascript:throw new Error('React has blocked a javascript: URL as a security precaution.')" : l;
  }
  function Zt() {
  }
  var Pi = null;
  function lc(l) {
    return l = l.target || l.srcElement || window, l.correspondingUseElement && (l = l.correspondingUseElement), l.nodeType === 3 ? l.parentNode : l;
  }
  var Du = null, Mu = null;
  function eo(l) {
    var t = zu(l);
    if (t && (l = t.stateNode)) {
      var a = l[kl] || null;
      l: switch (l = t.stateNode, t.type) {
        case "input":
          if (Wi(
            l,
            a.value,
            a.defaultValue,
            a.defaultValue,
            a.checked,
            a.defaultChecked,
            a.type,
            a.name
          ), t = a.name, a.type === "radio" && t != null) {
            for (a = l; a.parentNode; ) a = a.parentNode;
            for (a = a.querySelectorAll(
              'input[name="' + zt(
                "" + t
              ) + '"][type="radio"]'
            ), t = 0; t < a.length; t++) {
              var u = a[t];
              if (u !== l && u.form === l.form) {
                var e = u[kl] || null;
                if (!e) throw Error(r(90));
                Wi(
                  u,
                  e.value,
                  e.defaultValue,
                  e.defaultValue,
                  e.checked,
                  e.defaultChecked,
                  e.type,
                  e.name
                );
              }
            }
            for (t = 0; t < a.length; t++)
              u = a[t], u.form === l.form && Is(u);
          }
          break l;
        case "textarea":
          lo(l, a.value, a.defaultValue);
          break l;
        case "select":
          t = a.value, t != null && Ou(l, !!a.multiple, t, !1);
      }
    }
  }
  var tc = !1;
  function no(l, t, a) {
    if (tc) return l(t, a);
    tc = !0;
    try {
      var u = l(t);
      return u;
    } finally {
      if (tc = !1, (Du !== null || Mu !== null) && (Ti(), Du && (t = Du, l = Mu, Mu = Du = null, eo(t), l)))
        for (t = 0; t < l.length; t++) eo(l[t]);
    }
  }
  function ye(l, t) {
    var a = l.stateNode;
    if (a === null) return null;
    var u = a[kl] || null;
    if (u === null) return null;
    a = u[t];
    l: switch (t) {
      case "onClick":
      case "onClickCapture":
      case "onDoubleClick":
      case "onDoubleClickCapture":
      case "onMouseDown":
      case "onMouseDownCapture":
      case "onMouseMove":
      case "onMouseMoveCapture":
      case "onMouseUp":
      case "onMouseUpCapture":
      case "onMouseEnter":
        (u = !u.disabled) || (l = l.type, u = !(l === "button" || l === "input" || l === "select" || l === "textarea")), l = !u;
        break l;
      default:
        l = !1;
    }
    if (l) return null;
    if (a && typeof a != "function")
      throw Error(
        r(231, t, typeof a)
      );
    return a;
  }
  var ea = !(typeof window > "u" || typeof window.document > "u" || typeof window.document.createElement > "u"), ac = !1;
  if (ea)
    try {
      var ge = {};
      Object.defineProperty(ge, "passive", {
        get: function() {
          ac = !0;
        }
      }), window.addEventListener("test", ge, ge), window.removeEventListener("test", ge, ge);
    } catch {
      ac = !1;
    }
  var ba = null, uc = null, En = null;
  function io() {
    if (En) return En;
    var l, t = uc, a = t.length, u, e = "value" in ba ? ba.value : ba.textContent, n = e.length;
    for (l = 0; l < a && t[l] === e[l]; l++) ;
    var i = a - l;
    for (u = 1; u <= i && t[a - u] === e[n - u]; u++) ;
    return En = e.slice(l, 1 < u ? 1 - u : void 0);
  }
  function bn(l) {
    var t = l.keyCode;
    return "charCode" in l ? (l = l.charCode, l === 0 && t === 13 && (l = 13)) : l = t, l === 10 && (l = 13), 32 <= l || l === 13 ? l : 0;
  }
  function zn() {
    return !0;
  }
  function co() {
    return !1;
  }
  function Kl(l) {
    function t(a, u, e, n, i) {
      this._reactName = a, this._targetInst = e, this.type = u, this.nativeEvent = n, this.target = i, this.currentTarget = null;
      for (var c in l)
        l.hasOwnProperty(c) && (a = l[c], this[c] = a ? a(n) : n[c]);
      return this.isDefaultPrevented = (n.defaultPrevented != null ? n.defaultPrevented : n.returnValue === !1) ? zn : co, this.isPropagationStopped = co, this;
    }
    return K(t.prototype, {
      preventDefault: function() {
        this.defaultPrevented = !0;
        var a = this.nativeEvent;
        a && (a.preventDefault ? a.preventDefault() : typeof a.returnValue != "unknown" && (a.returnValue = !1), this.isDefaultPrevented = zn);
      },
      stopPropagation: function() {
        var a = this.nativeEvent;
        a && (a.stopPropagation ? a.stopPropagation() : typeof a.cancelBubble != "unknown" && (a.cancelBubble = !0), this.isPropagationStopped = zn);
      },
      persist: function() {
      },
      isPersistent: zn
    }), t;
  }
  var za = {
    eventPhase: 0,
    bubbles: 0,
    cancelable: 0,
    timeStamp: function(l) {
      return l.timeStamp || Date.now();
    },
    defaultPrevented: 0,
    isTrusted: 0
  }, Nn = Kl(za), Se = K({}, za, { view: 0, detail: 0 }), xm = Kl(Se), ec, nc, Te, _n = K({}, Se, {
    screenX: 0,
    screenY: 0,
    clientX: 0,
    clientY: 0,
    pageX: 0,
    pageY: 0,
    ctrlKey: 0,
    shiftKey: 0,
    altKey: 0,
    metaKey: 0,
    getModifierState: cc,
    button: 0,
    buttons: 0,
    relatedTarget: function(l) {
      return l.relatedTarget === void 0 ? l.fromElement === l.srcElement ? l.toElement : l.fromElement : l.relatedTarget;
    },
    movementX: function(l) {
      return "movementX" in l ? l.movementX : (l !== Te && (Te && l.type === "mousemove" ? (ec = l.screenX - Te.screenX, nc = l.screenY - Te.screenY) : nc = ec = 0, Te = l), ec);
    },
    movementY: function(l) {
      return "movementY" in l ? l.movementY : nc;
    }
  }), fo = Kl(_n), Hm = K({}, _n, { dataTransfer: 0 }), pm = Kl(Hm), Ym = K({}, Se, { relatedTarget: 0 }), ic = Kl(Ym), qm = K({}, za, {
    animationName: 0,
    elapsedTime: 0,
    pseudoElement: 0
  }), Bm = Kl(qm), Gm = K({}, za, {
    clipboardData: function(l) {
      return "clipboardData" in l ? l.clipboardData : window.clipboardData;
    }
  }), Xm = Kl(Gm), Qm = K({}, za, { data: 0 }), so = Kl(Qm), Vm = {
    Esc: "Escape",
    Spacebar: " ",
    Left: "ArrowLeft",
    Up: "ArrowUp",
    Right: "ArrowRight",
    Down: "ArrowDown",
    Del: "Delete",
    Win: "OS",
    Menu: "ContextMenu",
    Apps: "ContextMenu",
    Scroll: "ScrollLock",
    MozPrintableKey: "Unidentified"
  }, Zm = {
    8: "Backspace",
    9: "Tab",
    12: "Clear",
    13: "Enter",
    16: "Shift",
    17: "Control",
    18: "Alt",
    19: "Pause",
    20: "CapsLock",
    27: "Escape",
    32: " ",
    33: "PageUp",
    34: "PageDown",
    35: "End",
    36: "Home",
    37: "ArrowLeft",
    38: "ArrowUp",
    39: "ArrowRight",
    40: "ArrowDown",
    45: "Insert",
    46: "Delete",
    112: "F1",
    113: "F2",
    114: "F3",
    115: "F4",
    116: "F5",
    117: "F6",
    118: "F7",
    119: "F8",
    120: "F9",
    121: "F10",
    122: "F11",
    123: "F12",
    144: "NumLock",
    145: "ScrollLock",
    224: "Meta"
  }, Lm = {
    Alt: "altKey",
    Control: "ctrlKey",
    Meta: "metaKey",
    Shift: "shiftKey"
  };
  function Km(l) {
    var t = this.nativeEvent;
    return t.getModifierState ? t.getModifierState(l) : (l = Lm[l]) ? !!t[l] : !1;
  }
  function cc() {
    return Km;
  }
  var Jm = K({}, Se, {
    key: function(l) {
      if (l.key) {
        var t = Vm[l.key] || l.key;
        if (t !== "Unidentified") return t;
      }
      return l.type === "keypress" ? (l = bn(l), l === 13 ? "Enter" : String.fromCharCode(l)) : l.type === "keydown" || l.type === "keyup" ? Zm[l.keyCode] || "Unidentified" : "";
    },
    code: 0,
    location: 0,
    ctrlKey: 0,
    shiftKey: 0,
    altKey: 0,
    metaKey: 0,
    repeat: 0,
    locale: 0,
    getModifierState: cc,
    charCode: function(l) {
      return l.type === "keypress" ? bn(l) : 0;
    },
    keyCode: function(l) {
      return l.type === "keydown" || l.type === "keyup" ? l.keyCode : 0;
    },
    which: function(l) {
      return l.type === "keypress" ? bn(l) : l.type === "keydown" || l.type === "keyup" ? l.keyCode : 0;
    }
  }), wm = Kl(Jm), Fm = K({}, _n, {
    pointerId: 0,
    width: 0,
    height: 0,
    pressure: 0,
    tangentialPressure: 0,
    tiltX: 0,
    tiltY: 0,
    twist: 0,
    pointerType: 0,
    isPrimary: 0
  }), oo = Kl(Fm), $m = K({}, za, { submitter: 0 }), Wm = Kl($m), km = K({}, Se, {
    touches: 0,
    targetTouches: 0,
    changedTouches: 0,
    altKey: 0,
    metaKey: 0,
    ctrlKey: 0,
    shiftKey: 0,
    getModifierState: cc
  }), Im = Kl(km), Pm = K({}, za, {
    propertyName: 0,
    elapsedTime: 0,
    pseudoElement: 0
  }), lh = Kl(Pm), th = K({}, _n, {
    deltaX: function(l) {
      return "deltaX" in l ? l.deltaX : "wheelDeltaX" in l ? -l.wheelDeltaX : 0;
    },
    deltaY: function(l) {
      return "deltaY" in l ? l.deltaY : "wheelDeltaY" in l ? -l.wheelDeltaY : "wheelDelta" in l ? -l.wheelDelta : 0;
    },
    deltaZ: 0,
    deltaMode: 0
  }), ah = Kl(th), uh = K({}, za, {
    newState: 0,
    oldState: 0,
    source: 0
  }), eh = Kl(uh), nh = [9, 13, 27, 32], fc = ea && "CompositionEvent" in window, Ee = null;
  ea && "documentMode" in document && (Ee = document.documentMode);
  var ih = ea && "TextEvent" in window && !Ee, vo = ea && (!fc || Ee && 8 < Ee && 11 >= Ee), mo = " ", ho = !1;
  function ro(l, t) {
    switch (l) {
      case "keyup":
        return nh.indexOf(t.keyCode) !== -1;
      case "keydown":
        return t.keyCode !== 229;
      case "keypress":
      case "mousedown":
      case "focusout":
        return !0;
      default:
        return !1;
    }
  }
  function yo(l) {
    return l = l.detail, typeof l == "object" && "data" in l ? l.data : null;
  }
  var ju = !1;
  function ch(l, t) {
    switch (l) {
      case "compositionend":
        return yo(t);
      case "keypress":
        return t.which !== 32 ? null : (ho = !0, mo);
      case "textInput":
        return l = t.data, l === mo && ho ? null : l;
      default:
        return null;
    }
  }
  function fh(l, t) {
    if (ju)
      return l === "compositionend" || !fc && ro(l, t) ? (l = io(), En = uc = ba = null, ju = !1, l) : null;
    switch (l) {
      case "paste":
        return null;
      case "keypress":
        if (!(t.ctrlKey || t.altKey || t.metaKey) || t.ctrlKey && t.altKey) {
          if (t.char && 1 < t.char.length)
            return t.char;
          if (t.which) return String.fromCharCode(t.which);
        }
        return null;
      case "compositionend":
        return vo && t.locale !== "ko" ? null : t.data;
      default:
        return null;
    }
  }
  var sh = {
    color: !0,
    date: !0,
    datetime: !0,
    "datetime-local": !0,
    email: !0,
    month: !0,
    number: !0,
    password: !0,
    range: !0,
    search: !0,
    tel: !0,
    text: !0,
    time: !0,
    url: !0,
    week: !0
  };
  function go(l) {
    var t = l && l.nodeName && l.nodeName.toLowerCase();
    return t === "input" ? !!sh[l.type] : t === "textarea";
  }
  function So(l, t, a, u) {
    Du ? Mu ? Mu.push(u) : Mu = [u] : Du = u, t = Oi(t, "onChange"), 0 < t.length && (a = new Nn(
      "onChange",
      "change",
      null,
      a,
      u
    ), l.push({ event: a, listeners: t }));
  }
  var be = null, ze = null;
  function oh(l) {
    nd(l, 0);
  }
  function On(l) {
    var t = re(l);
    if (Is(t)) return l;
  }
  function To(l, t) {
    if (l === "change") return t;
  }
  var Eo = !1;
  if (ea) {
    var sc;
    if (ea) {
      var oc = "oninput" in document;
      if (!oc) {
        var bo = document.createElement("div");
        bo.setAttribute("oninput", "return;"), oc = typeof bo.oninput == "function";
      }
      sc = oc;
    } else sc = !1;
    Eo = sc && (!document.documentMode || 9 < document.documentMode);
  }
  function zo() {
    be && (be.detachEvent("onpropertychange", No), ze = be = null);
  }
  function No(l) {
    if (l.propertyName === "value" && On(ze)) {
      var t = [];
      So(
        t,
        ze,
        l,
        lc(l)
      ), no(oh, t);
    }
  }
  function vh(l, t, a) {
    l === "focusin" ? (zo(), be = t, ze = a, be.attachEvent("onpropertychange", No)) : l === "focusout" && zo();
  }
  function dh(l) {
    if (l === "selectionchange" || l === "keyup" || l === "keydown")
      return On(ze);
  }
  function mh(l, t) {
    if (l === "click") return On(t);
  }
  function hh(l, t) {
    if (l === "input" || l === "change")
      return On(t);
  }
  function rh(l, t) {
    return l === t && (l !== 0 || 1 / l === 1 / t) || l !== l && t !== t;
  }
  var dt = typeof Object.is == "function" ? Object.is : rh;
  function Ne(l, t) {
    if (dt(l, t)) return !0;
    if (typeof l != "object" || l === null || typeof t != "object" || t === null)
      return !1;
    var a = Object.keys(l), u = Object.keys(t);
    if (a.length !== u.length) return !1;
    for (u = 0; u < a.length; u++) {
      var e = a[u];
      if (!Zi.call(t, e) || !dt(l[e], t[e]))
        return !1;
    }
    return !0;
  }
  function vc(l) {
    if (l = l || (typeof document < "u" ? document : void 0), typeof l > "u") return null;
    try {
      return l.activeElement || l.body;
    } catch {
      return l.body;
    }
  }
  function _o(l) {
    for (; l && l.firstChild; ) l = l.firstChild;
    return l;
  }
  function Oo(l, t) {
    var a = _o(l);
    l = 0;
    for (var u; a; ) {
      if (a.nodeType === 3) {
        if (u = l + a.textContent.length, l <= t && u >= t)
          return { node: a, offset: t - l };
        l = u;
      }
      l: {
        for (; a; ) {
          if (a.nextSibling) {
            a = a.nextSibling;
            break l;
          }
          a = a.parentNode;
        }
        a = void 0;
      }
      a = _o(a);
    }
  }
  function Ao(l, t) {
    return l && t ? l === t ? !0 : l && l.nodeType === 3 ? !1 : t && t.nodeType === 3 ? Ao(l, t.parentNode) : "contains" in l ? l.contains(t) : l.compareDocumentPosition ? !!(l.compareDocumentPosition(t) & 16) : !1 : !1;
  }
  function Do(l) {
    l = l != null && l.ownerDocument != null && l.ownerDocument.defaultView != null ? l.ownerDocument.defaultView : window;
    for (var t = vc(l.document); t instanceof l.HTMLIFrameElement; ) {
      try {
        var a = typeof t.contentWindow.location.href == "string";
      } catch {
        a = !1;
      }
      if (a) l = t.contentWindow;
      else break;
      t = vc(l.document);
    }
    return t;
  }
  function dc(l) {
    var t = l && l.nodeName && l.nodeName.toLowerCase();
    return t && (t === "input" && (l.type === "text" || l.type === "search" || l.type === "tel" || l.type === "url" || l.type === "password") || t === "textarea" || l.contentEditable === "true");
  }
  var yh = ea && "documentMode" in document && 11 >= document.documentMode, Uu = null, mc = null, _e = null, hc = !1;
  function Mo(l, t, a) {
    var u = a.window === a ? a.document : a.nodeType === 9 ? a : a.ownerDocument;
    hc || Uu == null || Uu !== vc(u) || (u = Uu, "selectionStart" in u && dc(u) ? u = { start: u.selectionStart, end: u.selectionEnd } : (u = (u.ownerDocument && u.ownerDocument.defaultView || window).getSelection(), u = {
      anchorNode: u.anchorNode,
      anchorOffset: u.anchorOffset,
      focusNode: u.focusNode,
      focusOffset: u.focusOffset
    }), _e && Ne(_e, u) || (_e = u, u = Oi(mc, "onSelect"), 0 < u.length && (t = new Nn(
      "onSelect",
      "select",
      null,
      t,
      a
    ), l.push({ event: t, listeners: u }), t.target = Uu)));
  }
  function Pa(l, t) {
    var a = {};
    return a[l.toLowerCase()] = t.toLowerCase(), a["Webkit" + l] = "webkit" + t, a["Moz" + l] = "moz" + t, a;
  }
  var Cu = {
    animationend: Pa("Animation", "AnimationEnd"),
    animationiteration: Pa("Animation", "AnimationIteration"),
    animationstart: Pa("Animation", "AnimationStart"),
    transitionrun: Pa("Transition", "TransitionRun"),
    transitionstart: Pa("Transition", "TransitionStart"),
    transitioncancel: Pa("Transition", "TransitionCancel"),
    transitionend: Pa("Transition", "TransitionEnd")
  }, rc = {}, jo = {};
  ea && (jo = document.createElement("div").style, "AnimationEvent" in window || (delete Cu.animationend.animation, delete Cu.animationiteration.animation, delete Cu.animationstart.animation), "TransitionEvent" in window || delete Cu.transitionend.transition);
  function lu(l) {
    if (rc[l]) return rc[l];
    if (!Cu[l]) return l;
    var t = Cu[l], a;
    for (a in t)
      if (t.hasOwnProperty(a) && a in jo)
        return rc[l] = t[a];
    return l;
  }
  var Uo = lu("animationend"), Co = lu("animationiteration"), Ro = lu("animationstart"), gh = lu("transitionrun"), Sh = lu("transitionstart"), Th = lu("transitioncancel"), xo = lu("transitionend"), Ho = /* @__PURE__ */ new Map(), yc = "abort auxClick beforeToggle cancel canPlay canPlayThrough click close contextMenu copy cut drag dragEnd dragEnter dragExit dragLeave dragOver dragStart drop durationChange emptied encrypted ended error fullscreenChange fullscreenError gotPointerCapture input invalid keyDown keyPress keyUp load loadedData loadedMetadata loadStart lostPointerCapture mouseDown mouseMove mouseOut mouseOver mouseUp paste pause play playing pointerCancel pointerDown pointerMove pointerOut pointerOver pointerUp progress rateChange reset resize seeked seeking stalled submit suspend timeUpdate touchCancel touchEnd touchStart volumeChange scroll toggle touchMove waiting wheel".split(
    " "
  );
  yc.push("scrollEnd");
  function Rt(l, t) {
    Ho.set(l, t), Ia(t, [l]);
  }
  var Eh = 0;
  function na(l, t) {
    if (l.name != null && l.name !== "auto") return l.name;
    if (t.autoName !== null) return t.autoName;
    l = Yt.identifierPrefix;
    var a = Eh++;
    return l = "_" + l + "t_" + a.toString(32) + "_", t.autoName = l;
  }
  function po(l) {
    if (l == null || typeof l == "string")
      return l;
    var t = null, a = ku;
    if (a !== null)
      for (var u = 0; u < a.length; u++) {
        var e = l[a[u]];
        if (e != null) {
          if (e === "none") return "none";
          t = t == null ? e : t + (" " + e);
        }
      }
    return t ?? l.default;
  }
  function ia(l, t) {
    return l = po(l), t = po(t), t == null ? l === "auto" ? null : l : t === "auto" ? null : t;
  }
  var An = typeof reportError == "function" ? reportError : function(l) {
    if (typeof window == "object" && typeof window.ErrorEvent == "function") {
      var t = new window.ErrorEvent("error", {
        bubbles: !0,
        cancelable: !0,
        message: typeof l == "object" && l !== null && typeof l.message == "string" ? String(l.message) : String(l),
        error: l
      });
      if (!window.dispatchEvent(t)) return;
    } else if (typeof process == "object" && typeof process.emit == "function") {
      process.emit("uncaughtException", l);
      return;
    }
    console.error(l);
  }, Nt = [], Ru = 0, gc = 0;
  function Dn() {
    for (var l = Ru, t = gc = Ru = 0; t < l; ) {
      var a = Nt[t];
      Nt[t++] = null;
      var u = Nt[t];
      Nt[t++] = null;
      var e = Nt[t];
      Nt[t++] = null;
      var n = Nt[t];
      if (Nt[t++] = null, u !== null && e !== null) {
        var i = u.pending;
        i === null ? e.next = e : (e.next = i.next, i.next = e), u.pending = e;
      }
      n !== 0 && Yo(a, e, n);
    }
  }
  function Mn(l, t, a, u) {
    Nt[Ru++] = l, Nt[Ru++] = t, Nt[Ru++] = a, Nt[Ru++] = u, gc |= u, l.lanes |= u, l = l.alternate, l !== null && (l.lanes |= u);
  }
  function Sc(l, t, a, u) {
    return Mn(l, t, a, u), jn(l);
  }
  function tu(l, t) {
    return Mn(l, null, null, t), jn(l);
  }
  function Yo(l, t, a) {
    l.lanes |= a;
    var u = l.alternate;
    u !== null && (u.lanes |= a);
    for (var e = !1, n = l.return; n !== null; )
      n.childLanes |= a, u = n.alternate, u !== null && (u.childLanes |= a), n.tag === 22 && (l = n.stateNode, l === null || l._visibility & 1 || (e = !0)), l = n, n = n.return;
    return l.tag === 3 ? (n = l.stateNode, e && t !== null && (e = 31 - ot(a), l = n.hiddenUpdates, u = l[e], u === null ? l[e] = [t] : u.push(t), t.lane = a | 536870912), n) : null;
  }
  function jn(l) {
    if (50 < Je)
      throw Je = 0, Si = null, Error(r(185));
    for (var t = l.return; t !== null; )
      l = t, t = l.return;
    return l.tag === 3 ? l.stateNode : null;
  }
  var xu = {};
  function bh(l, t, a, u) {
    this.tag = l, this.key = a, this.sibling = this.child = this.return = this.stateNode = this.type = this.elementType = null, this.index = 0, this.refCleanup = this.ref = null, this.pendingProps = t, this.dependencies = this.memoizedState = this.updateQueue = this.memoizedProps = null, this.mode = u, this.subtreeFlags = this.flags = 0, this.deletions = null, this.childLanes = this.lanes = 0, this.alternate = null;
  }
  function Il(l, t, a, u) {
    return new bh(l, t, a, u);
  }
  function Tc(l) {
    return l = l.prototype, !(!l || !l.isReactComponent);
  }
  function ca(l, t) {
    var a = l.alternate;
    return a === null ? (a = Il(
      l.tag,
      t,
      l.key,
      l.mode
    ), a.elementType = l.elementType, a.type = l.type, a.stateNode = l.stateNode, a.alternate = l, l.alternate = a) : (a.pendingProps = t, a.type = l.type, a.flags = 0, a.subtreeFlags = 0, a.deletions = null), a.flags = l.flags & 1206910976, a.childLanes = l.childLanes, a.lanes = l.lanes, a.child = l.child, a.memoizedProps = l.memoizedProps, a.memoizedState = l.memoizedState, a.updateQueue = l.updateQueue, t = l.dependencies, a.dependencies = t === null ? null : { lanes: t.lanes, firstContext: t.firstContext }, a.sibling = l.sibling, a.index = l.index, a.ref = l.ref, a.refCleanup = l.refCleanup, a;
  }
  function qo(l, t) {
    l.flags &= 1206910978;
    var a = l.alternate;
    return a === null ? (l.childLanes = 0, l.lanes = t, l.child = null, l.subtreeFlags = 0, l.memoizedProps = null, l.memoizedState = null, l.updateQueue = null, l.dependencies = null, l.stateNode = null) : (l.childLanes = a.childLanes, l.lanes = a.lanes, l.child = a.child, l.subtreeFlags = 0, l.deletions = null, l.memoizedProps = a.memoizedProps, l.memoizedState = a.memoizedState, l.updateQueue = a.updateQueue, l.type = a.type, t = a.dependencies, l.dependencies = t === null ? null : {
      lanes: t.lanes,
      firstContext: t.firstContext
    }), l;
  }
  function Un(l, t, a, u, e, n) {
    var i = 0;
    if (u = l, typeof u == "function") Tc(u) && (i = 1);
    else if (typeof u == "string")
      i = $r(
        l,
        a,
        Vt.current
      ) ? 26 : l === "html" || l === "head" || l === "body" ? 27 : 5;
    else
      l: switch (u) {
        case Ct:
          return l = Il(31, a, t, e), l.elementType = Ct, l.lanes = n, l;
        case bt:
          return au(a.children, e, n, t);
        case ct:
          i = 8, e |= 24;
          break;
        case ga:
          return l = Il(12, a, t, e | 2), l.elementType = ga, l.lanes = n, l;
        case Y:
          return l = Il(13, a, t, e), l.elementType = Y, l.lanes = n, l;
        case q:
          return l = Il(19, a, t, e), l.elementType = q, l.lanes = n, l;
        case la:
        case v:
          return l = e | 32, l = Il(30, a, t, l), l.elementType = v, l.lanes = n, l.stateNode = {
            autoName: null,
            paired: null,
            clones: null,
            ref: null
          }, l;
        default:
          if (typeof u == "object" && u !== null)
            switch (u.$$typeof) {
              case Ul:
                i = 10;
                break l;
              case Fa:
                i = 9;
                break l;
              case O:
                i = 11;
                break l;
              case ol:
                i = 14;
                break l;
              case el:
                i = 16, u = null;
                break l;
            }
          i = 29, a = Error(
            r(130, l === null ? "null" : typeof l, "")
          ), u = null;
      }
    return t = Il(i, a, t, e), t.elementType = l, t.type = u, t.lanes = n, t;
  }
  function au(l, t, a, u) {
    return l = Il(7, l, u, t), l.lanes = a, l;
  }
  function Ec(l, t, a) {
    return l = Il(6, l, null, t), l.lanes = a, l;
  }
  function Bo(l) {
    var t = Il(18, null, null, 0);
    return t.stateNode = l, t;
  }
  function bc(l, t, a) {
    return t = Il(
      4,
      l.children !== null ? l.children : [],
      l.key,
      t
    ), t.lanes = a, t.stateNode = {
      containerInfo: l.containerInfo,
      pendingChildren: null,
      implementation: l.implementation
    }, t;
  }
  var Go = /* @__PURE__ */ new WeakMap();
  function _t(l, t) {
    if (typeof l == "object" && l !== null) {
      var a = Go.get(l);
      return a !== void 0 ? a : (t = {
        value: l,
        source: t,
        stack: Rs(t)
      }, Go.set(l, t), t);
    }
    return {
      value: l,
      source: t,
      stack: Rs(t)
    };
  }
  var Hu = [], pu = 0, Cn = null, Oe = 0, Ot = [], At = 0, Na = null, Lt = 1, Kt = "";
  function fa(l, t) {
    Hu[pu++] = Oe, Hu[pu++] = Cn, Cn = l, Oe = t;
  }
  function Xo(l, t, a) {
    Ot[At++] = Lt, Ot[At++] = Kt, Ot[At++] = Na, Na = l;
    var u = Lt;
    l = Kt;
    var e = 32 - ot(u) - 1;
    u &= ~(1 << e), a += 1;
    var n = 32 - ot(t) + e;
    if (30 < n) {
      var i = e - e % 5;
      n = (u & (1 << i) - 1).toString(32), u >>= i, e -= i, Lt = 1 << 32 - ot(t) + e | a << e | u, Kt = n + l;
    } else
      Lt = 1 << n | a << e | u, Kt = l;
  }
  function Rn(l) {
    l.return !== null && (fa(l, 1), Xo(l, 1, 0));
  }
  function zc(l) {
    for (; l === Cn; )
      Cn = Hu[--pu], Hu[pu] = null, Oe = Hu[--pu], Hu[pu] = null;
    for (; l === Na; )
      Na = Ot[--At], Ot[At] = null, Kt = Ot[--At], Ot[At] = null, Lt = Ot[--At], Ot[At] = null;
  }
  function Qo(l, t) {
    Ot[At++] = Lt, Ot[At++] = Kt, Ot[At++] = Na, Lt = t.id, Kt = t.overflow, Na = l;
  }
  var Rl = null, hl = null, L = !1, _a = null, Dt = !1, Nc = Error(r(519));
  function Oa(l) {
    var t = Error(
      r(
        418,
        1 < arguments.length && arguments[1] !== void 0 && arguments[1] ? "text" : "HTML",
        ""
      )
    );
    throw Ae(_t(t, l)), Nc;
  }
  function Vo(l) {
    var t = l.stateNode, a = l.type, u = l.memoizedProps;
    switch (t[ql] = l, t[kl] = u, a) {
      case "dialog":
        w("cancel", t), w("close", t);
        break;
      case "iframe":
      case "object":
      case "embed":
        w("load", t);
        break;
      case "video":
      case "audio":
        for (a = 0; a < Fe.length; a++)
          w(Fe[a], t);
        break;
      case "source":
        w("error", t);
        break;
      case "img":
      case "image":
      case "link":
        w("error", t), w("load", t);
        break;
      case "details":
        w("toggle", t);
        break;
      case "input":
        w("invalid", t), Ps(
          t,
          u.value,
          u.defaultValue,
          u.checked,
          u.defaultChecked,
          u.type,
          u.name,
          !0
        );
        break;
      case "select":
        w("invalid", t);
        break;
      case "textarea":
        w("invalid", t), to(t, u.value, u.defaultValue, u.children);
    }
    a = u.children, typeof a != "string" && typeof a != "number" && typeof a != "bigint" || t.textContent === "" + a || u.suppressHydrationWarning === !0 || sd(t.textContent, a) ? (u.popover != null && (w("beforetoggle", t), w("toggle", t)), u.onScroll != null && w("scroll", t), u.onScrollEnd != null && w("scrollend", t), u.onClick != null && (t.onclick = Zt), t = !0) : t = !1, t || Oa(l, !0);
  }
  function xn(l) {
    for (Rl = l.return; Rl; )
      switch (Rl.tag) {
        case 5:
        case 31:
        case 13:
          Dt = !1;
          return;
        case 27:
        case 3:
          Dt = !0;
          return;
        default:
          Rl = Rl.return;
      }
  }
  function Yu(l) {
    if (l !== Rl) return !1;
    if (!L) return xn(l), L = !0, !1;
    var t = l.tag, a;
    if ((a = t !== 3 && t !== 27) && ((a = t === 5) && (a = l.type, a = !(a !== "form" && a !== "button") || ls(l.type, l.memoizedProps)), a = !a), a && hl && Oa(l), xn(l), t === 13) {
      if (l = l.memoizedState, l = l !== null ? l.dehydrated : null, !l) throw Error(r(317));
      hl = Md(l);
    } else if (t === 31) {
      if (l = l.memoizedState, l = l !== null ? l.dehydrated : null, !l) throw Error(r(317));
      hl = Md(l);
    } else
      t === 27 ? (t = hl, Qa(l.type) ? (l = ss, ss = null, hl = l) : hl = t) : hl = Rl ? jt(l.stateNode.nextSibling) : null;
    return !0;
  }
  function uu() {
    hl = Rl = null, L = !1;
  }
  function _c() {
    var l = _a;
    return l !== null && (tt === null ? tt = l : tt.push.apply(
      tt,
      l
    ), _a = null), l;
  }
  function Ae(l) {
    _a === null ? _a = [l] : _a.push(l);
  }
  var Oc = Qt(null), eu = null, sa = null;
  function Aa(l, t, a) {
    ml(Oc, t._currentValue), t._currentValue = a;
  }
  function oa(l) {
    l._currentValue = Oc.current, Yl(Oc);
  }
  function Hn(l, t, a) {
    for (; l !== null; ) {
      var u = l.alternate;
      if ((l.childLanes & t) !== t ? (l.childLanes |= t, u !== null && (u.childLanes |= t)) : u !== null && (u.childLanes & t) !== t && (u.childLanes |= t), l === a) break;
      l = l.return;
    }
  }
  function Ac(l, t, a, u) {
    var e = l.child;
    for (e !== null && (e.return = l); e !== null; ) {
      var n = e.dependencies;
      if (n !== null) {
        var i = e.child;
        n = n.firstContext;
        l: for (; n !== null; ) {
          var c = n;
          n = e;
          for (var f = 0; f < t.length; f++)
            if (c.context === t[f]) {
              n.lanes |= a, c = n.alternate, c !== null && (c.lanes |= a), Hn(
                n.return,
                a,
                l
              ), u || (i = null);
              break l;
            }
          n = c.next;
        }
      } else if (e.tag === 18) {
        if (i = e.return, i === null) throw Error(r(341));
        i.lanes |= a, n = i.alternate, n !== null && (n.lanes |= a), Hn(i, a, l), i = null;
      } else
        e.tag === 13 && e.memoizedState !== null && e.memoizedState.dehydrated === null ? (e.lanes |= a, i = e.alternate, i !== null && (i.lanes |= a), Hn(
          e.return,
          a,
          l
        ), i = e.child, i = i !== null ? i.sibling : null) : i = e.child;
      if (i !== null) i.return = e;
      else
        for (i = e; i !== null; ) {
          if (i === l) {
            i = null;
            break;
          }
          if (e = i.sibling, e !== null) {
            e.return = i.return, i = e;
            break;
          }
          i = i.return;
        }
      e = i;
    }
  }
  function nu(l, t, a, u) {
    l = null;
    for (var e = t, n = !1; e !== null; ) {
      if (!n) {
        if ((e.flags & 524288) !== 0) n = !0;
        else if ((e.flags & 262144) !== 0) break;
      }
      if (e.tag === 10) {
        var i = e.alternate;
        if (i === null) throw Error(r(387));
        if (i = i.memoizedProps, i !== null) {
          var c = e.type;
          dt(e.pendingProps.value, i.value) || (l !== null ? l.push(c) : l = [c]);
        }
      } else if (e === cn.current) {
        if (i = e.alternate, i === null) throw Error(r(387));
        i.memoizedState.memoizedState !== e.memoizedState.memoizedState && (l !== null ? l.push(ce) : l = [ce]);
      }
      e = e.return;
    }
    return l !== null && Ac(
      t,
      l,
      a,
      u
    ), t.flags |= 262144, l !== null;
  }
  function pn(l) {
    for (l = l.firstContext; l !== null; ) {
      if (!dt(
        l.context._currentValue,
        l.memoizedValue
      ))
        return !0;
      l = l.next;
    }
    return !1;
  }
  function iu(l) {
    eu = l, sa = null, l = l.dependencies, l !== null && (l.firstContext = null);
  }
  function Bl(l) {
    return Zo(eu, l);
  }
  function Yn(l, t) {
    return eu === null && iu(l), Zo(l, t);
  }
  function Zo(l, t) {
    var a = t._currentValue;
    if (t = { context: t, memoizedValue: a, next: null }, sa === null) {
      if (l === null) throw Error(r(308));
      sa = t, l.dependencies = { lanes: 0, firstContext: t }, l.flags |= 524288;
    } else sa = sa.next = t;
    return a;
  }
  var zh = typeof AbortController < "u" ? AbortController : function() {
    var l = [], t = this.signal = {
      aborted: !1,
      addEventListener: function(a, u) {
        l.push(u);
      }
    };
    this.abort = function() {
      t.aborted = !0, l.forEach(function(a) {
        return a();
      });
    };
  }, Nh = z.unstable_scheduleCallback, _h = z.unstable_NormalPriority, zl = {
    $$typeof: Ul,
    Consumer: null,
    Provider: null,
    _currentValue: null,
    _currentValue2: null,
    _threadCount: 0
  };
  function Dc() {
    return {
      controller: new zh(),
      data: /* @__PURE__ */ new Map(),
      refCount: 0
    };
  }
  function De(l) {
    l.refCount--, l.refCount === 0 && Nh(_h, function() {
      l.controller.abort();
    });
  }
  function Lo(l, t) {
    if ((l.pendingLanes & 4194048) !== 0) {
      var a = l.transitionTypes;
      for (a === null && (a = l.transitionTypes = []), l = 0; l < t.length; l++) {
        var u = t[l];
        a.indexOf(u) === -1 && a.push(u);
      }
    }
  }
  var Me = null;
  function Oh(l) {
    var t = l.transitionTypes;
    return l.transitionTypes = null, t;
  }
  var je = null, Mc = 0, cu = 0, qu = null;
  function Ah(l, t) {
    if (je === null) {
      var a = je = [];
      Mc = 0, cu = Kf(), qu = {
        status: "pending",
        value: void 0,
        then: function(u) {
          a.push(u);
        }
      };
    }
    return Mc++, t.then(Ko, Ko), t;
  }
  function Ko() {
    if (--Mc === 0 && (Me = null, je !== null)) {
      qu !== null && (qu.status = "fulfilled");
      var l = je;
      je = null, cu = 0, qu = null;
      for (var t = 0; t < l.length; t++) (0, l[t])();
    }
  }
  function Dh(l, t) {
    var a = [], u = {
      status: "pending",
      value: null,
      reason: null,
      then: function(e) {
        a.push(e);
      }
    };
    return l.then(
      function() {
        u.status = "fulfilled", u.value = t;
        for (var e = 0; e < a.length; e++) (0, a[e])(t);
      },
      function(e) {
        for (u.status = "rejected", u.reason = e, e = 0; e < a.length; e++)
          (0, a[e])(void 0);
      }
    ), u;
  }
  var Jo = M.S;
  M.S = function(l, t) {
    if (Bv = ft(), typeof t == "object" && t !== null && typeof t.then == "function" && Ah(l, t), Me !== null)
      for (var a = te; a !== null; )
        Lo(a, Me), a = a.next;
    if (a = l.types, a !== null) {
      for (var u = te; u !== null; )
        Lo(u, a), u = u.next;
      if (cu !== 0) {
        u = Me, u === null && (u = Me = []);
        for (var e = 0; e < a.length; e++) {
          var n = a[e];
          u.indexOf(n) === -1 && u.push(n);
        }
      }
    }
    Jo !== null && Jo(l, t);
  };
  var fu = Qt(null);
  function jc() {
    var l = fu.current;
    return l !== null ? l : vl.pooledCache;
  }
  function qn(l, t) {
    t === null ? ml(fu, fu.current) : ml(fu, t.pool);
  }
  function wo() {
    var l = jc();
    return l === null ? null : { parent: zl._currentValue, pool: l };
  }
  var Bu = Error(r(460)), Uc = Error(r(474)), Bn = Error(r(542)), Gn = { then: function() {
  } };
  function Fo(l) {
    return l = l.status, l === "fulfilled" || l === "rejected";
  }
  function $o(l, t, a) {
    switch (a = l[a], a === void 0 ? l.push(t) : a !== t && (t.then(Zt, Zt), t = a), t.status) {
      case "fulfilled":
        return t.value;
      case "rejected":
        throw l = t.reason, ko(l), l === void 0 && !("reason" in t) ? Error(r(600)) : l;
      default:
        if (typeof t.status == "string") t.then(Zt, Zt);
        else {
          if (l = vl, l !== null && 100 < l.shellSuspendCounter)
            throw Error(r(482));
          l = t, l.status = "pending", l.then(
            function(u) {
              if (t.status === "pending") {
                var e = t;
                e.status = "fulfilled", e.value = u;
              }
            },
            function(u) {
              if (t.status === "pending") {
                var e = t;
                e.status = "rejected", e.reason = u;
              }
            }
          );
        }
        switch (t.status) {
          case "fulfilled":
            return t.value;
          case "rejected":
            throw l = t.reason, ko(l), l;
        }
        throw ou = t, Bu;
    }
  }
  function su(l) {
    try {
      var t = l._init;
      return t(l._payload);
    } catch (a) {
      throw a !== null && typeof a == "object" && typeof a.then == "function" ? (ou = a, Bu) : a;
    }
  }
  var ou = null;
  function Wo() {
    if (ou === null) throw Error(r(459));
    var l = ou;
    return ou = null, l;
  }
  function ko(l) {
    if (l === Bu || l === Bn)
      throw Error(r(483));
  }
  var Gu = null, Ue = 0;
  function Xn(l) {
    var t = Ue;
    return Ue += 1, Gu === null && (Gu = []), $o(Gu, l, t);
  }
  function Da(l, t) {
    t = t.props.ref, l.ref = t !== void 0 ? t : null;
  }
  function Qn(l, t) {
    throw t.$$typeof === tl ? Error(r(525)) : (l = Object.prototype.toString.call(t), Error(
      r(
        31,
        l === "[object Object]" ? "object with keys {" + Object.keys(t).join(", ") + "}" : l
      )
    ));
  }
  function Io(l) {
    function t(m, s) {
      if (l) {
        var y = m.deletions;
        y === null ? (m.deletions = [s], m.flags |= 16) : y.push(s);
      }
    }
    function a(m, s) {
      if (!l) return null;
      for (; s !== null; )
        t(m, s), s = s.sibling;
      return null;
    }
    function u(m) {
      for (var s = /* @__PURE__ */ new Map(); m !== null; )
        m.key === null ? s.set(m.index, m) : s.set(m.key, m), m = m.sibling;
      return s;
    }
    function e(m, s) {
      return m = ca(m, s), m.index = 0, m.sibling = null, m;
    }
    function n(m, s, y) {
      return m.index = y, l ? (y = m.alternate, y !== null ? (y = y.index, y < s ? (m.flags |= 2, s) : y) : (m.flags |= 134217730, s)) : (m.flags |= 1048576, s);
    }
    function i(m) {
      return l && m.alternate === null && (m.flags |= 134217730), m;
    }
    function c(m, s, y, T) {
      return s === null || s.tag !== 6 ? (s = Ec(y, m.mode, T), s.return = m, s) : (s = e(s, y), s.return = m, s);
    }
    function f(m, s, y, T) {
      var A = y.type;
      return A === bt ? (m = S(
        m,
        s,
        y.props.children,
        T,
        y.key
      ), Da(m, y), m) : s !== null && (s.elementType === A || typeof A == "object" && A !== null && A.$$typeof === el && su(A) === s.type) ? (s = e(s, y.props), Da(s, y), s.return = m, s) : (s = Un(
        y.type,
        y.key,
        y.props,
        null,
        m.mode,
        T
      ), Da(s, y), s.return = m, s);
    }
    function h(m, s, y, T) {
      return s === null || s.tag !== 4 || s.stateNode.containerInfo !== y.containerInfo || s.stateNode.implementation !== y.implementation ? (s = bc(y, m.mode, T), s.return = m, s) : (s = e(s, y.children || []), s.return = m, s);
    }
    function S(m, s, y, T, A) {
      return s === null || s.tag !== 7 ? (s = au(
        y,
        m.mode,
        T,
        A
      ), s.return = m, s) : (s = e(s, y), s.return = m, s);
    }
    function b(m, s, y) {
      if (typeof s == "string" && s !== "" || typeof s == "number" || typeof s == "bigint")
        return s = Ec(
          "" + s,
          m.mode,
          y
        ), s.return = m, s;
      if (typeof s == "object" && s !== null) {
        switch (s.$$typeof) {
          case Xt:
            return y = Un(
              s.type,
              s.key,
              s.props,
              null,
              m.mode,
              y
            ), Da(y, s), y.return = m, y;
          case Et:
            return s = bc(
              s,
              m.mode,
              y
            ), s.return = m, s;
          case el:
            return s = su(s), b(m, s, y);
        }
        if (al(s) || R(s))
          return s = au(
            s,
            m.mode,
            y,
            null
          ), s.return = m, s;
        if (typeof s.then == "function")
          return b(m, Xn(s), y);
        if (s.$$typeof === Ul)
          return b(
            m,
            Yn(m, s),
            y
          );
        Qn(m, s);
      }
      return null;
    }
    function d(m, s, y, T) {
      var A = s !== null ? s.key : null;
      if (typeof y == "string" && y !== "" || typeof y == "number" || typeof y == "bigint")
        return A !== null ? null : c(m, s, "" + y, T);
      if (typeof y == "object" && y !== null) {
        switch (y.$$typeof) {
          case Xt:
            return y.key === A ? f(m, s, y, T) : null;
          case Et:
            return y.key === A ? h(m, s, y, T) : null;
          case el:
            return y = su(y), d(m, s, y, T);
        }
        if (al(y) || R(y))
          return A !== null ? null : S(m, s, y, T, null);
        if (typeof y.then == "function")
          return d(
            m,
            s,
            Xn(y),
            T
          );
        if (y.$$typeof === Ul)
          return d(
            m,
            s,
            Yn(m, y),
            T
          );
        Qn(m, y);
      }
      return null;
    }
    function g(m, s, y, T, A) {
      if (typeof T == "string" && T !== "" || typeof T == "number" || typeof T == "bigint")
        return m = m.get(y) || null, c(s, m, "" + T, A);
      if (typeof T == "object" && T !== null) {
        switch (T.$$typeof) {
          case Xt:
            return m = m.get(
              T.key === null ? y : T.key
            ) || null, f(s, m, T, A);
          case Et:
            return m = m.get(
              T.key === null ? y : T.key
            ) || null, h(s, m, T, A);
          case el:
            return T = su(T), g(
              m,
              s,
              y,
              T,
              A
            );
        }
        if (al(T) || R(T))
          return m = m.get(y) || null, S(s, m, T, A, null);
        if (typeof T.then == "function")
          return g(
            m,
            s,
            y,
            Xn(T),
            A
          );
        if (T.$$typeof === Ul)
          return g(
            m,
            s,
            y,
            Yn(s, T),
            A
          );
        Qn(s, T);
      }
      return null;
    }
    function _(m, s, y, T) {
      for (var A = null, $ = null, j = s, x = s = 0, Ol = null; j !== null && x < y.length; x++) {
        j.index > x ? (Ol = j, j = null) : Ol = j.sibling;
        var W = d(
          m,
          j,
          y[x],
          T
        );
        if (W === null) {
          j === null && (j = Ol);
          break;
        }
        l && j && W.alternate === null && t(m, j), s = n(W, s, x), $ === null ? A = W : $.sibling = W, $ = W, j = Ol;
      }
      if (x === y.length)
        return a(m, j), L && fa(m, x), A;
      if (j === null) {
        for (; x < y.length; x++)
          j = b(m, y[x], T), j !== null && (s = n(
            j,
            s,
            x
          ), $ === null ? A = j : $.sibling = j, $ = j);
        return L && fa(m, x), A;
      }
      for (j = u(j); x < y.length; x++)
        Ol = g(
          j,
          m,
          x,
          y[x],
          T
        ), Ol !== null && (l && (W = Ol.alternate, W !== null && j.delete(W.key === null ? x : W.key)), s = n(
          Ol,
          s,
          x
        ), $ === null ? A = Ol : $.sibling = Ol, $ = Ol);
      return l && j.forEach(function(Ja) {
        return t(m, Ja);
      }), L && fa(m, x), A;
    }
    function D(m, s, y, T) {
      if (y == null) throw Error(r(151));
      for (var A = null, $ = null, j = s, x = s = 0, Ol = null, W = y.next(); j !== null && !W.done; x++, W = y.next()) {
        j.index > x ? (Ol = j, j = null) : Ol = j.sibling;
        var Ja = d(m, j, W.value, T);
        if (Ja === null) {
          j === null && (j = Ol);
          break;
        }
        l && j && Ja.alternate === null && t(m, j), s = n(Ja, s, x), $ === null ? A = Ja : $.sibling = Ja, $ = Ja, j = Ol;
      }
      if (W.done)
        return a(m, j), L && fa(m, x), A;
      if (j === null) {
        for (; !W.done; x++, W = y.next())
          W = b(m, W.value, T), W !== null && (s = n(W, s, x), $ === null ? A = W : $.sibling = W, $ = W);
        return L && fa(m, x), A;
      }
      for (j = u(j); !W.done; x++, W = y.next())
        W = g(j, m, x, W.value, T), W !== null && (l && (Ol = W.alternate, Ol !== null && j.delete(
          Ol.key === null ? x : Ol.key
        )), s = n(W, s, x), $ === null ? A = W : $.sibling = W, $ = W);
      return l && j.forEach(function(cy) {
        return t(m, cy);
      }), L && fa(m, x), A;
    }
    function Z(m, s, y, T) {
      if (typeof y == "object" && y !== null && y.type === bt && y.key === null && y.props.ref === void 0 && (y = y.props.children), typeof y == "object" && y !== null) {
        switch (y.$$typeof) {
          case Xt:
            l: {
              for (var A = y.key; s !== null; ) {
                if (s.key === A) {
                  if (A = y.type, A === bt) {
                    if (s.tag === 7) {
                      a(
                        m,
                        s.sibling
                      ), T = e(
                        s,
                        y.props.children
                      ), Da(T, y), T.return = m, m = T;
                      break l;
                    }
                  } else if (s.elementType === A || typeof A == "object" && A !== null && A.$$typeof === el && su(A) === s.type) {
                    a(
                      m,
                      s.sibling
                    ), T = e(s, y.props), Da(T, y), T.return = m, m = T;
                    break l;
                  }
                  a(m, s);
                  break;
                } else t(m, s);
                s = s.sibling;
              }
              y.type === bt ? (T = au(
                y.props.children,
                m.mode,
                T,
                y.key
              ), Da(T, y), T.return = m, m = T) : (T = Un(
                y.type,
                y.key,
                y.props,
                null,
                m.mode,
                T
              ), Da(T, y), T.return = m, m = T);
            }
            return i(m);
          case Et:
            l: {
              for (A = y.key; s !== null; ) {
                if (s.key === A)
                  if (s.tag === 4 && s.stateNode.containerInfo === y.containerInfo && s.stateNode.implementation === y.implementation) {
                    a(
                      m,
                      s.sibling
                    ), T = e(s, y.children || []), T.return = m, m = T;
                    break l;
                  } else {
                    a(m, s);
                    break;
                  }
                else t(m, s);
                s = s.sibling;
              }
              T = bc(y, m.mode, T), T.return = m, m = T;
            }
            return i(m);
          case el:
            return y = su(y), Z(
              m,
              s,
              y,
              T
            );
        }
        if (al(y))
          return _(
            m,
            s,
            y,
            T
          );
        if (R(y)) {
          if (A = R(y), typeof A != "function") throw Error(r(150));
          return y = A.call(y), D(
            m,
            s,
            y,
            T
          );
        }
        if (typeof y.then == "function")
          return Z(
            m,
            s,
            Xn(y),
            T
          );
        if (y.$$typeof === Ul)
          return Z(
            m,
            s,
            Yn(m, y),
            T
          );
        Qn(m, y);
      }
      return typeof y == "string" && y !== "" || typeof y == "number" || typeof y == "bigint" ? (y = "" + y, s !== null && s.tag === 6 ? (a(m, s.sibling), T = e(s, y), T.return = m, m = T) : (a(m, s), T = Ec(y, m.mode, T), T.return = m, m = T), i(m)) : a(m, s);
    }
    return function(m, s, y, T) {
      try {
        Ue = 0;
        var A = Z(
          m,
          s,
          y,
          T
        );
        return Gu = null, A;
      } catch (j) {
        if (j === Bu || j === Bn) throw j;
        var $ = Il(29, j, null, m.mode);
        return $.lanes = T, $.return = m, $;
      } finally {
      }
    };
  }
  var vu = Io(!0), Po = Io(!1), Ma = !1;
  function Cc(l) {
    l.updateQueue = {
      baseState: l.memoizedState,
      firstBaseUpdate: null,
      lastBaseUpdate: null,
      shared: { pending: null, lanes: 0, hiddenCallbacks: null },
      callbacks: null
    };
  }
  function Rc(l, t) {
    l = l.updateQueue, t.updateQueue === l && (t.updateQueue = {
      baseState: l.baseState,
      firstBaseUpdate: l.firstBaseUpdate,
      lastBaseUpdate: l.lastBaseUpdate,
      shared: l.shared,
      callbacks: null
    });
  }
  function ja(l) {
    return { lane: l, tag: 0, payload: null, callback: null, next: null };
  }
  function Ua(l, t, a) {
    var u = l.updateQueue;
    if (u === null) return null;
    if (u = u.shared, (ul & 2) !== 0) {
      var e = u.pending;
      return e === null ? t.next = t : (t.next = e.next, e.next = t), u.pending = t, t = jn(l), Yo(l, null, a), t;
    }
    return Mn(l, u, t, a), jn(l);
  }
  function Ce(l, t, a) {
    if (t = t.updateQueue, t !== null && (t = t.shared, (a & 4194048) !== 0)) {
      var u = t.lanes;
      u &= l.pendingLanes, a |= u, t.lanes = a, Gs(l, a);
    }
  }
  function xc(l, t) {
    var a = l.updateQueue, u = l.alternate;
    if (u !== null && (u = u.updateQueue, a === u)) {
      var e = null, n = null;
      if (a = a.firstBaseUpdate, a !== null) {
        do {
          var i = {
            lane: a.lane,
            tag: a.tag,
            payload: a.payload,
            callback: null,
            next: null
          };
          n === null ? e = n = i : n = n.next = i, a = a.next;
        } while (a !== null);
        n === null ? e = n = t : n = n.next = t;
      } else e = n = t;
      a = {
        baseState: u.baseState,
        firstBaseUpdate: e,
        lastBaseUpdate: n,
        shared: u.shared,
        callbacks: u.callbacks
      }, l.updateQueue = a;
      return;
    }
    l = a.lastBaseUpdate, l === null ? a.firstBaseUpdate = t : l.next = t, a.lastBaseUpdate = t;
  }
  var Hc = !1;
  function Re() {
    if (Hc) {
      var l = qu;
      if (l !== null) throw l;
    }
  }
  function xe(l, t, a, u) {
    Hc = !1;
    var e = l.updateQueue;
    Ma = !1;
    var n = e.firstBaseUpdate, i = e.lastBaseUpdate, c = e.shared.pending;
    if (c !== null) {
      e.shared.pending = null;
      var f = c, h = f.next;
      f.next = null, i === null ? n = h : i.next = h, i = f;
      var S = l.alternate;
      S !== null && (S = S.updateQueue, c = S.lastBaseUpdate, c !== i && (c === null ? S.firstBaseUpdate = h : c.next = h, S.lastBaseUpdate = f));
    }
    if (n !== null) {
      var b = e.baseState;
      i = 0, S = h = f = null, c = n;
      do {
        var d = c.lane & -536870913, g = d !== c.lane;
        if (g ? (F & d) === d : (u & d) === d) {
          d !== 0 && d === cu && (Hc = !0), S !== null && (S = S.next = {
            lane: 0,
            tag: c.tag,
            payload: c.payload,
            callback: null,
            next: null
          });
          l: {
            var _ = l, D = c;
            d = t;
            var Z = a;
            switch (D.tag) {
              case 1:
                if (_ = D.payload, typeof _ == "function") {
                  b = _.call(Z, b, d);
                  break l;
                }
                b = _;
                break l;
              case 3:
                _.flags = _.flags & -65537 | 128;
              case 0:
                if (_ = D.payload, d = typeof _ == "function" ? _.call(Z, b, d) : _, d == null) break l;
                b = K({}, b, d);
                break l;
              case 2:
                Ma = !0;
            }
          }
          d = c.callback, d !== null && (l.flags |= 64, g && (l.flags |= 8192), g = e.callbacks, g === null ? e.callbacks = [d] : g.push(d));
        } else
          g = {
            lane: d,
            tag: c.tag,
            payload: c.payload,
            callback: c.callback,
            next: null
          }, S === null ? (h = S = g, f = b) : S = S.next = g, i |= d;
        if (c = c.next, c === null) {
          if (c = e.shared.pending, c === null)
            break;
          g = c, c = g.next, g.next = null, e.lastBaseUpdate = g, e.shared.pending = null;
        }
      } while (!0);
      S === null && (f = b), e.baseState = f, e.firstBaseUpdate = h, e.lastBaseUpdate = S, n === null && (e.shared.lanes = 0), qa |= i, l.lanes = i, l.memoizedState = b;
    }
  }
  function l0(l, t) {
    if (typeof l != "function")
      throw Error(r(191, l));
    l.call(t);
  }
  function t0(l, t) {
    var a = l.callbacks;
    if (a !== null)
      for (l.callbacks = null, l = 0; l < a.length; l++)
        l0(a[l], t);
  }
  var Ca = Qt(null), Vn = Qt(0);
  function a0(l, t) {
    l = ra, ml(Vn, l), ml(Ca, t), ra = l | t.baseLanes;
  }
  function pc() {
    ml(Vn, ra), ml(Ca, Ca.current);
  }
  function Yc() {
    ra = Vn.current, Yl(Ca), Yl(Vn);
  }
  var Gl = Qt(null), Ll = null;
  function Ra(l) {
    var t = l.alternate;
    ml(Xl, Xl.current & 1), ml(Gl, l), Ll === null && (t === null || Ca.current !== null || t.memoizedState !== null) && (Ll = l);
  }
  function qc(l) {
    ml(Xl, Xl.current), ml(Gl, l), Ll === null && (Ll = l);
  }
  function u0(l) {
    l.tag === 22 ? (ml(Xl, Xl.current), ml(Gl, l), Ll === null && (Ll = l)) : xa();
  }
  function xa() {
    ml(Xl, Xl.current), ml(Gl, Gl.current);
  }
  function mt(l) {
    Yl(Gl), Ll === l && (Ll = null), Yl(Xl);
  }
  var Xl = Qt(0);
  function He(l, t) {
    ml(Gl, Gl.current), ml(Xl, t);
  }
  function Bc(l) {
    Yl(Xl), Yl(Gl), Ll === l && (Ll = null);
  }
  function Zn(l) {
    for (var t = l; t !== null; ) {
      if (t.tag === 13) {
        var a = t.memoizedState;
        if (a !== null && (a = a.dehydrated, a === null || cs(a) || fs(a)))
          return t;
      } else if (t.tag === 19 && t.memoizedProps.revealOrder !== "independent") {
        if ((t.flags & 128) !== 0) return t;
      } else if (t.child !== null) {
        t.child.return = t, t = t.child;
        continue;
      }
      if (t === l) break;
      for (; t.sibling === null; ) {
        if (t.return === null || t.return === l) return null;
        t = t.return;
      }
      t.sibling.return = t.return, t = t.sibling;
    }
    return null;
  }
  var va = 0, V = null, sl = null, Nl = null, Ln = !1, Xu = !1, du = !1, Kn = 0, pe = 0, Qu = null, Mh = 0;
  function Sl() {
    throw Error(r(321));
  }
  function Gc(l, t) {
    if (t === null) return !1;
    for (var a = 0; a < t.length && a < l.length; a++)
      if (!dt(l[a], t[a])) return !1;
    return !0;
  }
  function Xc(l, t, a, u, e, n) {
    return va = n, V = t, t.memoizedState = null, t.updateQueue = null, t.lanes = 0, M.H = l === null || l.memoizedState === null ? X0 : Q0, du = !1, n = a(u, e), du = !1, Xu && (n = n0(
      t,
      a,
      u,
      e
    )), e0(l), n;
  }
  function e0(l) {
    M.H = In;
    var t = sl !== null && sl.next !== null;
    if (va = 0, Nl = sl = V = null, Ln = !1, pe = 0, Qu = null, t) throw Error(r(300));
    l === null || _l || (l = l.dependencies, l !== null && pn(l) && (_l = !0));
  }
  function n0(l, t, a, u) {
    V = l;
    var e = 0;
    do {
      if (Xu && (Qu = null), pe = 0, Xu = !1, 25 <= e) throw Error(r(301));
      if (e += 1, Nl = sl = null, l.updateQueue != null) {
        var n = l.updateQueue;
        n.lastEffect = null, n.events = null, n.stores = null, n.memoCache != null && (n.memoCache.index = 0);
      }
      M.H = Yh, n = t(a, u);
    } while (Xu);
    return n;
  }
  function jh() {
    var l = M.H, t = l.useState()[0];
    return t = typeof t.then == "function" ? Ye(t) : t, l = l.useState()[0], (sl !== null ? sl.memoizedState : null) !== l && (V.flags |= 1024), t;
  }
  function Qc() {
    var l = Kn !== 0;
    return Kn = 0, l;
  }
  function Vc(l, t, a) {
    t.updateQueue = l.updateQueue, t.flags &= -2053, l.lanes &= ~a;
  }
  function Zc(l) {
    if (Ln) {
      for (l = l.memoizedState; l !== null; ) {
        var t = l.queue;
        t !== null && (t.pending = null), l = l.next;
      }
      Ln = !1;
    }
    va = 0, Nl = sl = V = null, Xu = !1, pe = Kn = 0, Qu = null;
  }
  function Jl() {
    var l = {
      memoizedState: null,
      baseState: null,
      baseQueue: null,
      queue: null,
      next: null
    };
    return Nl === null ? V.memoizedState = Nl = l : Nl = Nl.next = l, Nl;
  }
  function El() {
    if (sl === null) {
      var l = V.alternate;
      l = l !== null ? l.memoizedState : null;
    } else l = sl.next;
    var t = Nl === null ? V.memoizedState : Nl.next;
    if (t !== null)
      Nl = t, sl = l;
    else {
      if (l === null)
        throw V.alternate === null ? Error(r(467)) : Error(r(310));
      sl = l, l = {
        memoizedState: sl.memoizedState,
        baseState: sl.baseState,
        baseQueue: sl.baseQueue,
        queue: sl.queue,
        next: null
      }, Nl === null ? V.memoizedState = Nl = l : Nl = Nl.next = l;
    }
    return Nl;
  }
  function Jn() {
    return { lastEffect: null, events: null, stores: null, memoCache: null };
  }
  function Ye(l) {
    var t = pe;
    return pe += 1, Qu === null && (Qu = []), l = $o(Qu, l, t), t = V, (Nl === null ? t.memoizedState : Nl.next) === null && (t = t.alternate, M.H = t === null || t.memoizedState === null ? X0 : Q0), l;
  }
  function wn(l) {
    if (l !== null && typeof l == "object") {
      if (typeof l.then == "function") return Ye(l);
      if (l.$$typeof === N) return;
      if (l.$$typeof === Ul) return Bl(l);
    }
    throw Error(r(438, String(l)));
  }
  function Lc(l) {
    var t = null, a = V.updateQueue;
    if (a !== null && (t = a.memoCache), t == null) {
      var u = V.alternate;
      u !== null && (u = u.updateQueue, u !== null && (u = u.memoCache, u != null && (t = {
        data: u.data.map(function(e) {
          return e.slice();
        }),
        index: 0
      })));
    }
    if (t == null && (t = { data: [], index: 0 }), a === null && (a = Jn(), V.updateQueue = a), a.memoCache = t, a = t.data[t.index], a === void 0)
      for (a = t.data[t.index] = Array(l), u = 0; u < l; u++)
        a[u] = $a;
    return t.index++, a;
  }
  function da(l, t) {
    return typeof t == "function" ? t(l) : t;
  }
  function Fn(l) {
    var t = El();
    return Kc(t, sl, l);
  }
  function Kc(l, t, a) {
    var u = l.queue;
    if (u === null) throw Error(r(311));
    u.lastRenderedReducer = a;
    var e = l.baseQueue, n = u.pending;
    if (n !== null) {
      if (e !== null) {
        var i = e.next;
        e.next = n.next, n.next = i;
      }
      t.baseQueue = e = n, u.pending = null;
    }
    if (n = l.baseState, e === null) l.memoizedState = n;
    else {
      t = e.next;
      var c = i = null, f = null, h = t, S = !1;
      do {
        var b = h.lane & -536870913;
        if (b !== h.lane ? (F & b) === b : (va & b) === b) {
          var d = h.revertLane;
          if (d === 0)
            f !== null && (f = f.next = {
              lane: 0,
              revertLane: 0,
              gesture: null,
              action: h.action,
              hasEagerState: h.hasEagerState,
              eagerState: h.eagerState,
              next: null
            }), b === cu && (S = !0);
          else if ((va & d) === d) {
            h = h.next, d === cu && (S = !0);
            continue;
          } else
            b = {
              lane: 0,
              revertLane: h.revertLane,
              gesture: null,
              action: h.action,
              hasEagerState: h.hasEagerState,
              eagerState: h.eagerState,
              next: null
            }, f === null ? (c = f = b, i = n) : f = f.next = b, V.lanes |= d, qa |= d;
          b = h.action, du && a(n, b), n = h.hasEagerState ? h.eagerState : a(n, b);
        } else
          d = {
            lane: b,
            revertLane: h.revertLane,
            gesture: h.gesture,
            action: h.action,
            hasEagerState: h.hasEagerState,
            eagerState: h.eagerState,
            next: null
          }, f === null ? (c = f = d, i = n) : f = f.next = d, V.lanes |= b, qa |= b;
        h = h.next;
      } while (h !== null && h !== t);
      if (f === null ? i = n : f.next = c, !dt(n, l.memoizedState) && (_l = !0, S && (a = qu, a !== null)))
        throw a;
      l.memoizedState = n, l.baseState = i, l.baseQueue = f, u.lastRenderedState = n;
    }
    return e === null && (u.lanes = 0), [l.memoizedState, u.dispatch];
  }
  function Jc(l) {
    var t = El(), a = t.queue;
    if (a === null) throw Error(r(311));
    a.lastRenderedReducer = l;
    var u = a.dispatch, e = a.pending, n = t.memoizedState;
    if (e !== null) {
      a.pending = null;
      var i = e = e.next;
      do
        n = l(n, i.action), i = i.next;
      while (i !== e);
      dt(n, t.memoizedState) || (_l = !0), t.memoizedState = n, t.baseQueue === null && (t.baseState = n), a.lastRenderedState = n;
    }
    return [n, u];
  }
  function i0(l, t, a) {
    var u = V, e = El(), n = L;
    if (n) {
      if (a === void 0) throw Error(r(407));
      a = a();
    } else a = t();
    var i = !dt(
      (sl || e).memoizedState,
      a
    );
    if (i && (e.memoizedState = a, _l = !0), e = e.queue, $c(s0.bind(null, u, e, l), [
      l
    ]), l = e.getSnapshot !== t || i || Nl !== null && (Nl.memoizedState.tag & 1) !== 0, Vu(
      l ? 9 : 8,
      { destroy: void 0 },
      f0.bind(null, u, e, a, t),
      null
    ), l) {
      if (u.flags |= 2048, vl === null) throw Error(r(349));
      n || (va & 127) !== 0 || c0(u, t, a);
    }
    return a;
  }
  function c0(l, t, a) {
    l.flags |= 16384, l = { getSnapshot: t, value: a }, t = V.updateQueue, t === null ? (t = Jn(), V.updateQueue = t, t.stores = [l]) : (a = t.stores, a === null ? t.stores = [l] : a.push(l));
  }
  function f0(l, t, a, u) {
    t.value = a, t.getSnapshot = u, o0(t) && v0(l);
  }
  function s0(l, t, a) {
    return a(function() {
      o0(t) && v0(l);
    });
  }
  function o0(l) {
    var t = l.getSnapshot;
    l = l.value;
    try {
      var a = t();
      return !dt(l, a);
    } catch {
      return !0;
    }
  }
  function v0(l) {
    var t = tu(l, 2);
    t !== null && at(t, l, 2);
  }
  function wc(l) {
    var t = Jl();
    if (typeof l == "function") {
      var a = l;
      if (l = a(), du) {
        Ea(!0);
        try {
          a();
        } finally {
          Ea(!1);
        }
      }
    }
    return t.memoizedState = t.baseState = l, t.queue = {
      pending: null,
      lanes: 0,
      dispatch: null,
      lastRenderedReducer: da,
      lastRenderedState: l
    }, t;
  }
  function d0(l, t, a, u) {
    return l.baseState = a, Kc(
      l,
      sl,
      typeof u == "function" ? u : da
    );
  }
  function Uh(l, t, a, u, e) {
    if (kn(l)) throw Error(r(485));
    if (l = t.action, l !== null) {
      var n = {
        payload: e,
        action: l,
        next: null,
        isTransition: !0,
        status: "pending",
        value: null,
        reason: null,
        listeners: [],
        then: function(i) {
          n.listeners.push(i);
        }
      };
      M.T !== null ? a(!0) : n.isTransition = !1, u(n), a = t.pending, a === null ? (n.next = t.pending = n, m0(t, n)) : (n.next = a.next, t.pending = a.next = n);
    }
  }
  function m0(l, t) {
    var a = t.action, u = t.payload, e = l.state;
    if (t.isTransition) {
      var n = M.T, i = {};
      i.types = n !== null ? n.types : null, M.T = i;
      try {
        var c = a(e, u), f = M.S;
        f !== null && f(i, c), h0(l, t, c);
      } catch (h) {
        Fc(l, t, h);
      } finally {
        n !== null && i.types !== null && (n.types = i.types), M.T = n;
      }
    } else
      try {
        n = a(e, u), h0(l, t, n);
      } catch (h) {
        Fc(l, t, h);
      }
  }
  function h0(l, t, a) {
    a !== null && typeof a == "object" && typeof a.then == "function" ? a.then(
      function(u) {
        r0(l, t, u);
      },
      function(u) {
        return Fc(l, t, u);
      }
    ) : r0(l, t, a);
  }
  function r0(l, t, a) {
    t.status = "fulfilled", t.value = a, y0(t), l.state = a, t = l.pending, t !== null && (a = t.next, a === t ? l.pending = null : (a = a.next, t.next = a, m0(l, a)));
  }
  function Fc(l, t, a) {
    var u = l.pending;
    if (l.pending = null, u !== null) {
      u = u.next;
      do
        t.status = "rejected", t.reason = a, y0(t), t = t.next;
      while (t !== u);
    }
    l.action = null;
  }
  function y0(l) {
    l = l.listeners;
    for (var t = 0; t < l.length; t++) (0, l[t])();
  }
  function g0(l, t) {
    return t;
  }
  function S0(l, t) {
    if (L) {
      var a = vl.formState;
      if (a !== null) {
        l: {
          var u = V;
          if (L) {
            if (hl) {
              t: {
                for (var e = hl, n = Dt; e.nodeType !== 8; ) {
                  if (!n) {
                    e = null;
                    break t;
                  }
                  if (e = jt(
                    e.nextSibling
                  ), e === null) {
                    e = null;
                    break t;
                  }
                }
                n = e.data, e = n === "F!" || n === "F" ? e : null;
              }
              if (e) {
                hl = jt(
                  e.nextSibling
                ), u = e.data === "F!";
                break l;
              }
            }
            Oa(u);
          }
          u = !1;
        }
        u && (t = a[0]);
      }
    }
    return a = Jl(), a.memoizedState = a.baseState = t, u = {
      pending: null,
      lanes: 0,
      dispatch: null,
      lastRenderedReducer: g0,
      lastRenderedState: t
    }, a.queue = u, a = q0.bind(
      null,
      V,
      u
    ), u.dispatch = a, u = wc(!1), n = lf.bind(
      null,
      V,
      !1,
      u.queue
    ), u = Jl(), e = {
      state: t,
      dispatch: null,
      action: l,
      pending: null
    }, u.queue = e, a = Uh.bind(
      null,
      V,
      e,
      n,
      a
    ), e.dispatch = a, u.memoizedState = l, [t, a, !1];
  }
  function T0(l) {
    var t = El();
    return E0(t, sl, l);
  }
  function E0(l, t, a) {
    if (t = Kc(
      l,
      t,
      g0
    )[0], l = Fn(da)[0], typeof t == "object" && t !== null && typeof t.then == "function")
      try {
        var u = Ye(t);
      } catch (i) {
        throw i === Bu ? Bn : i;
      }
    else u = t;
    t = El();
    var e = t.queue, n = e.dispatch;
    return a !== t.memoizedState && (V.flags |= 2048, Vu(
      9,
      { destroy: void 0 },
      Ch.bind(null, e, a),
      null
    )), [u, n, l];
  }
  function Ch(l, t) {
    l.action = t;
  }
  function b0(l) {
    var t = El(), a = sl;
    if (a !== null)
      return E0(t, a, l);
    El(), t = t.memoizedState, a = El();
    var u = a.queue.dispatch;
    return a.memoizedState = l, [t, u, !1];
  }
  function Vu(l, t, a, u) {
    return l = { tag: l, create: a, deps: u, inst: t, next: null }, t = V.updateQueue, t === null && (t = Jn(), V.updateQueue = t), a = t.lastEffect, a === null ? t.lastEffect = l.next = l : (u = a.next, a.next = l, l.next = u, t.lastEffect = l), l;
  }
  function z0() {
    return El().memoizedState;
  }
  function $n(l, t, a, u) {
    var e = Jl();
    V.flags |= l, e.memoizedState = Vu(
      1 | t,
      { destroy: void 0 },
      a,
      u === void 0 ? null : u
    );
  }
  function Wn(l, t, a, u) {
    var e = El();
    u = u === void 0 ? null : u;
    var n = e.memoizedState.inst;
    sl !== null && u !== null && Gc(u, sl.memoizedState.deps) ? e.memoizedState = Vu(t, n, a, u) : (V.flags |= l, e.memoizedState = Vu(
      1 | t,
      n,
      a,
      u
    ));
  }
  function N0(l, t) {
    $n(8390656, 8, l, t);
  }
  function $c(l, t) {
    Wn(2048, 8, l, t);
  }
  function Rh(l) {
    V.flags |= 4;
    var t = V.updateQueue;
    if (t === null)
      t = Jn(), V.updateQueue = t, t.events = [l];
    else {
      var a = t.events;
      a === null ? t.events = [l] : a.push(l);
    }
  }
  function _0(l) {
    var t = El().memoizedState;
    return Rh({ ref: t, nextImpl: l }), function() {
      if ((ul & 2) !== 0) throw Error(r(440));
      return t.impl.apply(void 0, arguments);
    };
  }
  function O0(l, t) {
    return Wn(4, 2, l, t);
  }
  function A0(l, t) {
    return Wn(4, 4, l, t);
  }
  function D0(l, t) {
    if (typeof t == "function") {
      l = l();
      var a = t(l);
      return function() {
        typeof a == "function" ? a() : t(null);
      };
    }
    if (t != null)
      return l = l(), t.current = l, function() {
        t.current = null;
      };
  }
  function M0(l, t, a) {
    a = a != null ? a.concat([l]) : null, Wn(4, 4, D0.bind(null, t, l), a);
  }
  function Wc() {
  }
  function j0(l, t) {
    var a = El();
    t = t === void 0 ? null : t;
    var u = a.memoizedState;
    return t !== null && Gc(t, u[1]) ? u[0] : (a.memoizedState = [l, t], l);
  }
  function U0(l, t) {
    var a = El();
    t = t === void 0 ? null : t;
    var u = a.memoizedState;
    if (t !== null && Gc(t, u[1]))
      return u[0];
    if (u = l(), du) {
      Ea(!0);
      try {
        l();
      } finally {
        Ea(!1);
      }
    }
    return a.memoizedState = [u, t], u;
  }
  function kc(l, t, a) {
    return a === void 0 || (va & 1073741824) !== 0 && (F & 261930) === 0 ? l.memoizedState = t : (l.memoizedState = a, l = Xv(), V.lanes |= l, qa |= l, a);
  }
  function C0(l, t, a, u) {
    return dt(a, t) ? a : Ca.current !== null ? (l = kc(l, a, u), dt(l, t) || (_l = !0), l) : (va & 106) === 0 || (va & 1073741824) !== 0 && (F & 261930) === 0 ? (_l = !0, l.memoizedState = a) : (l = Xv(), V.lanes |= l, qa |= l, t);
  }
  function R0(l, t, a, u, e) {
    var n = G.p;
    G.p = n !== 0 && 8 > n ? n : 8;
    var i = M.T, c = {};
    c.types = i !== null ? i.types : null, M.T = c, lf(l, !1, t, a);
    try {
      var f = e(), h = M.S;
      if (h !== null && h(c, f), f !== null && typeof f == "object" && typeof f.then == "function") {
        var S = Dh(
          f,
          u
        );
        qe(
          l,
          t,
          S,
          gt(l)
        );
      } else
        qe(
          l,
          t,
          u,
          gt(l)
        );
    } catch (b) {
      qe(
        l,
        t,
        { then: function() {
        }, status: "rejected", reason: b },
        gt()
      );
    } finally {
      G.p = n, i !== null && c.types !== null && (i.types = c.types), M.T = i;
    }
  }
  function xh() {
  }
  function Ic(l, t, a, u) {
    if (l.tag !== 5) throw Error(r(476));
    var e = x0(l).queue;
    R0(
      l,
      e,
      t,
      ta,
      a === null ? xh : function() {
        return H0(l), a(u);
      }
    );
  }
  function x0(l) {
    var t = l.memoizedState;
    if (t !== null) return t;
    t = {
      memoizedState: ta,
      baseState: ta,
      baseQueue: null,
      queue: {
        pending: null,
        lanes: 0,
        dispatch: null,
        lastRenderedReducer: da,
        lastRenderedState: ta
      },
      next: null
    };
    var a = {};
    return t.next = {
      memoizedState: a,
      baseState: a,
      baseQueue: null,
      queue: {
        pending: null,
        lanes: 0,
        dispatch: null,
        lastRenderedReducer: da,
        lastRenderedState: a
      },
      next: null
    }, l.memoizedState = t, l = l.alternate, l !== null && (l.memoizedState = t), t;
  }
  function H0(l) {
    var t = x0(l);
    t.next === null && (t = l.alternate.memoizedState), qe(
      l,
      t.next.queue,
      {},
      gt()
    );
  }
  function Pc() {
    return Bl(ce);
  }
  function p0() {
    return El().memoizedState;
  }
  function Y0() {
    return El().memoizedState;
  }
  function Hh(l) {
    for (var t = l.return; t !== null; ) {
      switch (t.tag) {
        case 24:
        case 3:
          var a = gt();
          l = ja(a);
          var u = Ua(t, l, a);
          u !== null && (at(u, t, a), Ce(u, t, a)), t = { cache: Dc() }, l.payload = t;
          return;
      }
      t = t.return;
    }
  }
  function ph(l, t, a) {
    var u = gt();
    a = {
      lane: u,
      revertLane: 0,
      gesture: null,
      action: a,
      hasEagerState: !1,
      eagerState: null,
      next: null
    }, kn(l) ? B0(t, a) : (a = Sc(l, t, a, u), a !== null && (at(a, l, u), G0(a, t, u)));
  }
  function q0(l, t, a) {
    var u = gt();
    qe(l, t, a, u);
  }
  function qe(l, t, a, u) {
    var e = {
      lane: u,
      revertLane: 0,
      gesture: null,
      action: a,
      hasEagerState: !1,
      eagerState: null,
      next: null
    };
    if (kn(l)) B0(t, e);
    else {
      var n = l.alternate;
      if (l.lanes === 0 && (n === null || n.lanes === 0) && (n = t.lastRenderedReducer, n !== null))
        try {
          var i = t.lastRenderedState, c = n(i, a);
          if (e.hasEagerState = !0, e.eagerState = c, dt(c, i))
            return Mn(l, t, e, 0), vl === null && Dn(), !1;
        } catch {
        } finally {
        }
      if (a = Sc(l, t, e, u), a !== null)
        return at(a, l, u), G0(a, t, u), !0;
    }
    return !1;
  }
  function lf(l, t, a, u) {
    if (u = {
      lane: 2,
      revertLane: Kf(),
      gesture: null,
      action: u,
      hasEagerState: !1,
      eagerState: null,
      next: null
    }, kn(l)) {
      if (t) throw Error(r(479));
    } else
      t = Sc(
        l,
        a,
        u,
        2
      ), t !== null && at(t, l, 2);
  }
  function kn(l) {
    var t = l.alternate;
    return l === V || t !== null && t === V;
  }
  function B0(l, t) {
    Xu = Ln = !0;
    var a = l.pending;
    a === null ? t.next = t : (t.next = a.next, a.next = t), l.pending = t;
  }
  function G0(l, t, a) {
    if ((a & 4194048) !== 0) {
      var u = t.lanes;
      u &= l.pendingLanes, a |= u, t.lanes = a, Gs(l, a);
    }
  }
  var In = {
    readContext: Bl,
    use: wn,
    useCallback: Sl,
    useContext: Sl,
    useEffect: Sl,
    useImperativeHandle: Sl,
    useLayoutEffect: Sl,
    useInsertionEffect: Sl,
    useMemo: Sl,
    useReducer: Sl,
    useRef: Sl,
    useState: Sl,
    useDebugValue: Sl,
    useDeferredValue: Sl,
    useTransition: Sl,
    useSyncExternalStore: Sl,
    useId: Sl,
    useHostTransitionStatus: Sl,
    useFormState: Sl,
    useActionState: Sl,
    useOptimistic: Sl,
    useMemoCache: Sl,
    useCacheRefresh: Sl,
    useEffectEvent: Sl
  }, X0 = {
    readContext: Bl,
    use: wn,
    useCallback: function(l, t) {
      return Jl().memoizedState = [
        l,
        t === void 0 ? null : t
      ], l;
    },
    useContext: Bl,
    useEffect: N0,
    useImperativeHandle: function(l, t, a) {
      a = a != null ? a.concat([l]) : null, $n(
        4194308,
        4,
        D0.bind(null, t, l),
        a
      );
    },
    useLayoutEffect: function(l, t) {
      return $n(4194308, 4, l, t);
    },
    useInsertionEffect: function(l, t) {
      $n(4, 2, l, t);
    },
    useMemo: function(l, t) {
      var a = Jl();
      t = t === void 0 ? null : t;
      var u = l();
      if (du) {
        Ea(!0);
        try {
          l();
        } finally {
          Ea(!1);
        }
      }
      return a.memoizedState = [u, t], u;
    },
    useReducer: function(l, t, a) {
      var u = Jl();
      if (a !== void 0) {
        var e = a(t);
        if (du) {
          Ea(!0);
          try {
            a(t);
          } finally {
            Ea(!1);
          }
        }
      } else e = t;
      return u.memoizedState = u.baseState = e, l = {
        pending: null,
        lanes: 0,
        dispatch: null,
        lastRenderedReducer: l,
        lastRenderedState: e
      }, u.queue = l, l = l.dispatch = ph.bind(
        null,
        V,
        l
      ), [u.memoizedState, l];
    },
    useRef: function(l) {
      var t = Jl();
      return l = { current: l }, t.memoizedState = l;
    },
    useState: function(l) {
      l = wc(l);
      var t = l.queue, a = q0.bind(null, V, t);
      return t.dispatch = a, [l.memoizedState, a];
    },
    useDebugValue: Wc,
    useDeferredValue: function(l, t) {
      var a = Jl();
      return kc(a, l, t);
    },
    useTransition: function() {
      var l = wc(!1);
      return l = R0.bind(
        null,
        V,
        l.queue,
        !0,
        !1
      ), Jl().memoizedState = l, [!1, l];
    },
    useSyncExternalStore: function(l, t, a) {
      var u = V, e = Jl();
      if (L) {
        if (a === void 0)
          throw Error(r(407));
        a = a();
      } else {
        if (a = t(), vl === null)
          throw Error(r(349));
        (F & 127) !== 0 || c0(u, t, a);
      }
      e.memoizedState = a;
      var n = { value: a, getSnapshot: t };
      return e.queue = n, N0(s0.bind(null, u, n, l), [
        l
      ]), u.flags |= 2048, Vu(
        9,
        { destroy: void 0 },
        f0.bind(
          null,
          u,
          n,
          a,
          t
        ),
        null
      ), a;
    },
    useId: function() {
      var l = Jl(), t = vl.identifierPrefix;
      if (L) {
        var a = Kt, u = Lt;
        a = (u & ~(1 << 32 - ot(u) - 1)).toString(32) + a, t = "_" + t + "R_" + a, a = Kn++, 0 < a && (t += "H" + a.toString(32)), t += "_";
      } else
        a = Mh++, t = "_" + t + "r_" + a.toString(32) + "_";
      return l.memoizedState = t;
    },
    useHostTransitionStatus: Pc,
    useFormState: S0,
    useActionState: S0,
    useOptimistic: function(l) {
      var t = Jl();
      t.memoizedState = t.baseState = l;
      var a = {
        pending: null,
        lanes: 0,
        dispatch: null,
        lastRenderedReducer: null,
        lastRenderedState: null
      };
      return t.queue = a, t = lf.bind(
        null,
        V,
        !0,
        a
      ), a.dispatch = t, [l, t];
    },
    useMemoCache: Lc,
    useCacheRefresh: function() {
      return Jl().memoizedState = Hh.bind(
        null,
        V
      );
    },
    useEffectEvent: function(l) {
      var t = Jl(), a = { impl: l };
      return t.memoizedState = a, function() {
        if ((ul & 2) !== 0)
          throw Error(r(440));
        return a.impl.apply(void 0, arguments);
      };
    }
  }, Q0 = {
    readContext: Bl,
    use: wn,
    useCallback: j0,
    useContext: Bl,
    useEffect: $c,
    useImperativeHandle: M0,
    useInsertionEffect: O0,
    useLayoutEffect: A0,
    useMemo: U0,
    useReducer: Fn,
    useRef: z0,
    useState: function() {
      return Fn(da);
    },
    useDebugValue: Wc,
    useDeferredValue: function(l, t) {
      var a = El();
      return C0(
        a,
        sl.memoizedState,
        l,
        t
      );
    },
    useTransition: function() {
      var l = Fn(da)[0], t = El().memoizedState;
      return [
        typeof l == "boolean" ? l : Ye(l),
        t
      ];
    },
    useSyncExternalStore: i0,
    useId: p0,
    useHostTransitionStatus: Pc,
    useFormState: T0,
    useActionState: T0,
    useOptimistic: function(l, t) {
      var a = El();
      return d0(a, sl, l, t);
    },
    useMemoCache: Lc,
    useCacheRefresh: Y0,
    useEffectEvent: _0
  }, Yh = {
    readContext: Bl,
    use: wn,
    useCallback: j0,
    useContext: Bl,
    useEffect: $c,
    useImperativeHandle: M0,
    useInsertionEffect: O0,
    useLayoutEffect: A0,
    useMemo: U0,
    useReducer: Jc,
    useRef: z0,
    useState: function() {
      return Jc(da);
    },
    useDebugValue: Wc,
    useDeferredValue: function(l, t) {
      var a = El();
      return sl === null ? kc(a, l, t) : C0(
        a,
        sl.memoizedState,
        l,
        t
      );
    },
    useTransition: function() {
      var l = Jc(da)[0], t = El().memoizedState;
      return [
        typeof l == "boolean" ? l : Ye(l),
        t
      ];
    },
    useSyncExternalStore: i0,
    useId: p0,
    useHostTransitionStatus: Pc,
    useFormState: b0,
    useActionState: b0,
    useOptimistic: function(l, t) {
      var a = El();
      return sl !== null ? d0(a, sl, l, t) : (a.baseState = l, [l, a.queue.dispatch]);
    },
    useMemoCache: Lc,
    useCacheRefresh: Y0,
    useEffectEvent: _0
  };
  function tf(l, t, a, u) {
    t = l.memoizedState, a = a(u, t), a = a == null ? t : K({}, t, a), l.memoizedState = a, l.lanes === 0 && (l.updateQueue.baseState = a);
  }
  var af = {
    enqueueSetState: function(l, t, a) {
      l = l._reactInternals;
      var u = gt(), e = ja(u);
      e.payload = t, a != null && (e.callback = a), t = Ua(l, e, u), t !== null && (at(t, l, u), Ce(t, l, u));
    },
    enqueueReplaceState: function(l, t, a) {
      l = l._reactInternals;
      var u = gt(), e = ja(u);
      e.tag = 1, e.payload = t, a != null && (e.callback = a), t = Ua(l, e, u), t !== null && (at(t, l, u), Ce(t, l, u));
    },
    enqueueForceUpdate: function(l, t) {
      l = l._reactInternals;
      var a = gt(), u = ja(a);
      u.tag = 2, t != null && (u.callback = t), t = Ua(l, u, a), t !== null && (at(t, l, a), Ce(t, l, a));
    }
  };
  function V0(l, t, a, u, e, n, i) {
    return l = l.stateNode, typeof l.shouldComponentUpdate == "function" ? l.shouldComponentUpdate(u, n, i) : t.prototype && t.prototype.isPureReactComponent ? !Ne(a, u) || !Ne(e, n) : !0;
  }
  function Z0(l, t, a, u) {
    l = t.state, typeof t.componentWillReceiveProps == "function" && t.componentWillReceiveProps(a, u), typeof t.UNSAFE_componentWillReceiveProps == "function" && t.UNSAFE_componentWillReceiveProps(a, u), t.state !== l && af.enqueueReplaceState(t, t.state, null);
  }
  function mu(l, t) {
    var a = t;
    if ("ref" in t) {
      a = {};
      for (var u in t)
        u !== "ref" && (a[u] = t[u]);
    }
    if (l = l.defaultProps) {
      a === t && (a = K({}, a));
      for (var e in l)
        a[e] === void 0 && (a[e] = l[e]);
    }
    return a;
  }
  function L0(l) {
    An(l);
  }
  function K0(l) {
    console.error(l);
  }
  function J0(l) {
    An(l);
  }
  function Pn(l, t) {
    try {
      var a = l.onUncaughtError;
      a(t.value, { componentStack: t.stack });
    } catch (u) {
      setTimeout(function() {
        throw u;
      });
    }
  }
  function w0(l, t, a) {
    try {
      var u = l.onCaughtError;
      u(a.value, {
        componentStack: a.stack,
        errorBoundary: t.tag === 1 ? t.stateNode : null
      });
    } catch (e) {
      setTimeout(function() {
        throw e;
      });
    }
  }
  function uf(l, t, a) {
    return a = ja(a), a.tag = 3, a.payload = { element: null }, a.callback = function() {
      Pn(l, t);
    }, a;
  }
  function F0(l) {
    return l = ja(l), l.tag = 3, l;
  }
  function $0(l, t, a, u) {
    var e = a.type.getDerivedStateFromError;
    if (typeof e == "function") {
      var n = u.value;
      l.payload = function() {
        return e(n);
      }, l.callback = function() {
        w0(t, a, u);
      };
    }
    var i = a.stateNode;
    i !== null && typeof i.componentDidCatch == "function" && (l.callback = function() {
      w0(t, a, u), typeof e != "function" && (Ba === null ? Ba = /* @__PURE__ */ new Set([this]) : Ba.add(this));
      var c = u.stack;
      this.componentDidCatch(u.value, {
        componentStack: c !== null ? c : ""
      });
    });
  }
  function qh(l, t, a, u, e) {
    if (a.flags |= 32768, u !== null && typeof u == "object" && typeof u.then == "function") {
      if (t = a.alternate, t !== null && nu(
        t,
        a,
        e,
        !0
      ), a = Gl.current, a !== null) {
        switch (a.tag) {
          case 31:
          case 13:
          case 19:
            return Ll === null ? Ei() : a.alternate === null && Tl === 0 && (Tl = 3), a.flags &= -257, a.flags |= 65536, a.lanes = e, u === Gn ? a.flags |= 16384 : (t = a.updateQueue, t === null ? a.updateQueue = /* @__PURE__ */ new Set([u]) : t.add(u), Vf(l, u, e)), !1;
          case 22:
            return a.flags |= 65536, u === Gn ? a.flags |= 16384 : (t = a.updateQueue, t === null ? (t = {
              transitions: null,
              markerInstances: null,
              retryQueue: /* @__PURE__ */ new Set([u])
            }, a.updateQueue = t) : (a = t.retryQueue, a === null ? t.retryQueue = /* @__PURE__ */ new Set([u]) : a.add(u)), Vf(l, u, e)), !1;
        }
        throw Error(r(435, a.tag));
      }
      return Vf(l, u, e), Ei(), !1;
    }
    if (L)
      return t = Gl.current, t !== null ? ((t.flags & 65536) === 0 && (t.flags |= 256), t.flags |= 65536, t.lanes = e, u !== Nc && (l = Error(r(422), { cause: u }), Ae(_t(l, a)))) : (u !== Nc && (t = Error(r(423), {
        cause: u
      }), Ae(
        _t(t, a)
      )), l = l.current.alternate, l.flags |= 65536, e &= -e, l.lanes |= e, u = _t(u, a), e = uf(
        l.stateNode,
        u,
        e
      ), xc(l, e), Tl !== 4 && (Tl = 2)), !1;
    var n = Error(r(520), { cause: u });
    if (n = _t(n, a), Ke === null ? Ke = [n] : Ke.push(n), Tl !== 4 && (Tl = 2), t === null) return !0;
    u = _t(u, a), a = t;
    do {
      switch (a.tag) {
        case 3:
          return a.flags |= 65536, l = e & -e, a.lanes |= l, l = uf(a.stateNode, u, l), xc(a, l), !1;
        case 1:
          if (t = a.type, n = a.stateNode, (a.flags & 128) === 0 && (typeof t.getDerivedStateFromError == "function" || n !== null && typeof n.componentDidCatch == "function" && (Ba === null || !Ba.has(n))))
            return a.flags |= 65536, e &= -e, a.lanes |= e, e = F0(e), $0(
              e,
              l,
              a,
              u
            ), xc(a, e), !1;
          break;
        case 22:
          if (a.memoizedState !== null)
            return a.flags |= 65536, !1;
      }
      a = a.return;
    } while (a !== null);
    return !1;
  }
  var ef = Error(r(461)), _l = !1;
  function Dl(l, t, a, u) {
    t.child = l === null ? Po(t, null, a, u) : vu(
      t,
      l.child,
      a,
      u
    );
  }
  function W0(l, t, a, u, e) {
    a = a.render;
    var n = t.ref;
    if ("ref" in u) {
      var i = {};
      for (var c in u)
        c !== "ref" && (i[c] = u[c]);
    } else i = u;
    return iu(t), u = Xc(
      l,
      t,
      a,
      i,
      n,
      e
    ), c = Qc(), l !== null && !_l ? (Vc(l, t, e), ma(l, t, e)) : (L && c && Rn(t), t.flags |= 1, Dl(l, t, u, e), t.child);
  }
  function k0(l, t, a, u, e) {
    if (l === null) {
      var n = a.type;
      return typeof n == "function" && !Tc(n) && n.defaultProps === void 0 && a.compare === null ? (t.tag = 15, t.type = n, I0(
        l,
        t,
        n,
        u,
        e
      )) : (l = Un(
        a.type,
        null,
        u,
        t,
        t.mode,
        e
      ), l.ref = t.ref, l.return = t, t.child = l);
    }
    if (n = l.child, !mf(l, e)) {
      var i = n.memoizedProps;
      if (a = a.compare, a = a !== null ? a : Ne, a(i, u) && l.ref === t.ref)
        return ma(l, t, e);
    }
    return t.flags |= 1, l = ca(n, u), l.ref = t.ref, l.return = t, t.child = l;
  }
  function I0(l, t, a, u, e) {
    if (l !== null) {
      var n = l.memoizedProps;
      if (Ne(n, u) && l.ref === t.ref)
        if (_l = !1, t.pendingProps = u = n, mf(l, e))
          (l.flags & 131072) !== 0 && (_l = !0);
        else
          return t.lanes = l.lanes, ma(l, t, e);
    }
    return nf(
      l,
      t,
      a,
      u,
      e
    );
  }
  function P0(l, t, a, u) {
    var e = u.children, n = l !== null ? l.memoizedState : null;
    if (l === null && t.stateNode === null && (t.stateNode = {
      _visibility: 1,
      _pendingMarkers: null,
      _retryCache: null,
      _transitions: null
    }), u.mode === "hidden") {
      if ((t.flags & 128) !== 0) {
        if (n = n !== null ? n.baseLanes | a : a, l !== null) {
          for (u = t.child = l.child, e = 0; u !== null; )
            e = e | u.lanes | u.childLanes, u = u.sibling;
          u = e & ~n;
        } else u = 0, t.child = null;
        return lv(
          l,
          t,
          n,
          a,
          u
        );
      }
      if ((a & 536870912) !== 0)
        t.memoizedState = { baseLanes: 0, cachePool: null }, l !== null && qn(
          t,
          n !== null ? n.cachePool : null
        ), n !== null ? a0(t, n) : pc(), u0(t);
      else
        return u = t.lanes = 536870912, lv(
          l,
          t,
          n !== null ? n.baseLanes | a : a,
          a,
          u
        );
    } else
      n !== null ? (qn(t, n.cachePool), a0(t, n), xa(), t.memoizedState = null) : (l !== null && qn(t, null), pc(), xa());
    return Dl(l, t, e, a), t.child;
  }
  function Be(l, t) {
    return l !== null && l.tag === 22 || t.stateNode !== null || (t.stateNode = {
      _visibility: 1,
      _pendingMarkers: null,
      _retryCache: null,
      _transitions: null
    }), t.sibling;
  }
  function lv(l, t, a, u, e) {
    var n = jc();
    return n = n === null ? null : { parent: zl._currentValue, pool: n }, t.memoizedState = {
      baseLanes: a,
      cachePool: n
    }, l !== null && qn(t, null), pc(), u0(t), l !== null && nu(l, t, u, !0), t.childLanes = e, null;
  }
  function li(l, t) {
    return t = ti(
      { mode: t.mode, children: t.children },
      l.mode
    ), t.ref = l.ref, l.child = t, t.return = l, t;
  }
  function tv(l, t, a) {
    return vu(t, l.child, null, a), l = li(t, t.pendingProps), l.flags |= 2, mt(t), t.memoizedState = null, l;
  }
  function Bh(l, t, a) {
    var u = t.pendingProps, e = (t.flags & 128) !== 0;
    if (t.flags &= -129, l === null) {
      if (L) {
        if (u.mode === "hidden")
          return l = li(t, u), t.lanes = 536870912, l.memoizedState = { baseLanes: 0, cachePool: null }, Be(null, l);
        if (qc(t), (l = hl) ? (l = Dd(
          l,
          Dt
        ), l = l !== null && l.data === "&" ? l : null, l !== null && (t.memoizedState = {
          dehydrated: l,
          treeContext: Na !== null ? { id: Lt, overflow: Kt } : null,
          retryLane: 536870912,
          hydrationErrors: null
        }, a = Bo(l), a.return = t, t.child = a, Rl = t, hl = null)) : l = null, l === null) throw Oa(t);
        return t.lanes = 536870912, null;
      }
      return li(t, u);
    }
    var n = l.memoizedState;
    if (n !== null) {
      var i = n.dehydrated;
      if (qc(t), e)
        if (t.flags & 256)
          t.flags &= -257, t = tv(
            l,
            t,
            a
          );
        else if (t.memoizedState !== null)
          t.child = l.child, t.flags |= 128, t = null;
        else throw Error(r(558));
      else if (_l || nu(l, t, a, !1), e = (a & l.childLanes) !== 0, _l || e) {
        if (Ca.current === null) {
          if (u = vl, u !== null && (i = Xs(u, a), i !== 0 && i !== n.retryLane))
            throw n.retryLane = i, tu(l, i), at(u, l, i), ef;
          Ei();
        }
        t = tv(
          l,
          t,
          a
        );
      } else
        l = n.treeContext, hl = jt(i.nextSibling), Rl = t, L = !0, _a = null, Dt = !1, l !== null && Qo(t, l), t = li(t, u), t.flags |= 134221824;
      return t;
    }
    return l = ca(l.child, {
      mode: u.mode,
      children: u.children
    }), l.ref = t.ref, t.child = l, l.return = t, l;
  }
  function Zu(l, t) {
    var a = t.ref;
    if (a === null)
      l !== null && l.ref !== null && (t.flags |= 4194816);
    else {
      if (typeof a != "function" && typeof a != "object")
        throw Error(r(284));
      (l === null || l.ref !== a) && (t.flags |= 4194816);
    }
  }
  function nf(l, t, a, u, e) {
    return iu(t), a = Xc(
      l,
      t,
      a,
      u,
      void 0,
      e
    ), u = Qc(), l !== null && !_l ? (Vc(l, t, e), ma(l, t, e)) : (L && u && Rn(t), t.flags |= 1, Dl(l, t, a, e), t.child);
  }
  function av(l, t, a, u, e, n) {
    return iu(t), t.updateQueue = null, a = n0(
      t,
      u,
      a,
      e
    ), e0(l), u = Qc(), l !== null && !_l ? (Vc(l, t, n), ma(l, t, n)) : (L && u && Rn(t), t.flags |= 1, Dl(l, t, a, n), t.child);
  }
  function uv(l, t, a, u, e) {
    if (iu(t), t.stateNode === null) {
      var n = xu, i = a.contextType;
      typeof i == "object" && i !== null && (n = Bl(i)), n = new a(u, n), t.memoizedState = n.state !== null && n.state !== void 0 ? n.state : null, n.updater = af, t.stateNode = n, n._reactInternals = t, n = t.stateNode, n.props = u, n.state = t.memoizedState, n.refs = {}, Cc(t), i = a.contextType, n.context = typeof i == "object" && i !== null ? Bl(i) : xu, n.state = t.memoizedState, i = a.getDerivedStateFromProps, typeof i == "function" && (tf(
        t,
        a,
        i,
        u
      ), n.state = t.memoizedState), typeof a.getDerivedStateFromProps == "function" || typeof n.getSnapshotBeforeUpdate == "function" || typeof n.UNSAFE_componentWillMount != "function" && typeof n.componentWillMount != "function" || (i = n.state, typeof n.componentWillMount == "function" && n.componentWillMount(), typeof n.UNSAFE_componentWillMount == "function" && n.UNSAFE_componentWillMount(), i !== n.state && af.enqueueReplaceState(n, n.state, null), xe(t, u, n, e), Re(), n.state = t.memoizedState), typeof n.componentDidMount == "function" && (t.flags |= 4194308), u = !0;
    } else if (l === null) {
      n = t.stateNode;
      var c = t.memoizedProps, f = mu(a, c);
      n.props = f;
      var h = n.context, S = a.contextType;
      i = xu, typeof S == "object" && S !== null && (i = Bl(S));
      var b = a.getDerivedStateFromProps;
      S = typeof b == "function" || typeof n.getSnapshotBeforeUpdate == "function", c = t.pendingProps !== c, S || typeof n.UNSAFE_componentWillReceiveProps != "function" && typeof n.componentWillReceiveProps != "function" || (c || h !== i) && Z0(
        t,
        n,
        u,
        i
      ), Ma = !1;
      var d = t.memoizedState;
      n.state = d, xe(t, u, n, e), Re(), h = t.memoizedState, c || d !== h || Ma ? (typeof b == "function" && (tf(
        t,
        a,
        b,
        u
      ), h = t.memoizedState), (f = Ma || V0(
        t,
        a,
        f,
        u,
        d,
        h,
        i
      )) ? (S || typeof n.UNSAFE_componentWillMount != "function" && typeof n.componentWillMount != "function" || (typeof n.componentWillMount == "function" && n.componentWillMount(), typeof n.UNSAFE_componentWillMount == "function" && n.UNSAFE_componentWillMount()), typeof n.componentDidMount == "function" && (t.flags |= 4194308)) : (typeof n.componentDidMount == "function" && (t.flags |= 4194308), t.memoizedProps = u, t.memoizedState = h), n.props = u, n.state = h, n.context = i, u = f) : (typeof n.componentDidMount == "function" && (t.flags |= 4194308), u = !1);
    } else {
      n = t.stateNode, Rc(l, t), i = t.memoizedProps, S = mu(a, i), n.props = S, b = t.pendingProps, d = n.context, h = a.contextType, f = xu, typeof h == "object" && h !== null && (f = Bl(h)), c = a.getDerivedStateFromProps, (h = typeof c == "function" || typeof n.getSnapshotBeforeUpdate == "function") || typeof n.UNSAFE_componentWillReceiveProps != "function" && typeof n.componentWillReceiveProps != "function" || (i !== b || d !== f) && Z0(
        t,
        n,
        u,
        f
      ), Ma = !1, d = t.memoizedState, n.state = d, xe(t, u, n, e), Re();
      var g = t.memoizedState;
      i !== b || d !== g || Ma || l !== null && l.dependencies !== null && pn(l.dependencies) ? (typeof c == "function" && (tf(
        t,
        a,
        c,
        u
      ), g = t.memoizedState), (S = Ma || V0(
        t,
        a,
        S,
        u,
        d,
        g,
        f
      ) || l !== null && l.dependencies !== null && pn(l.dependencies)) ? (h || typeof n.UNSAFE_componentWillUpdate != "function" && typeof n.componentWillUpdate != "function" || (typeof n.componentWillUpdate == "function" && n.componentWillUpdate(u, g, f), typeof n.UNSAFE_componentWillUpdate == "function" && n.UNSAFE_componentWillUpdate(
        u,
        g,
        f
      )), typeof n.componentDidUpdate == "function" && (t.flags |= 4), typeof n.getSnapshotBeforeUpdate == "function" && (t.flags |= 1024)) : (typeof n.componentDidUpdate != "function" || i === l.memoizedProps && d === l.memoizedState || (t.flags |= 4), typeof n.getSnapshotBeforeUpdate != "function" || i === l.memoizedProps && d === l.memoizedState || (t.flags |= 1024), t.memoizedProps = u, t.memoizedState = g), n.props = u, n.state = g, n.context = f, u = S) : (typeof n.componentDidUpdate != "function" || i === l.memoizedProps && d === l.memoizedState || (t.flags |= 4), typeof n.getSnapshotBeforeUpdate != "function" || i === l.memoizedProps && d === l.memoizedState || (t.flags |= 1024), u = !1);
    }
    return n = u, Zu(l, t), u = (t.flags & 128) !== 0, n || u ? (n = t.stateNode, a = u && typeof a.getDerivedStateFromError != "function" ? null : n.render(), t.flags |= 1, l !== null && u ? (t.child = vu(
      t,
      l.child,
      null,
      e
    ), t.child = vu(
      t,
      null,
      a,
      e
    )) : Dl(l, t, a, e), t.memoizedState = n.state, l = t.child) : l = ma(
      l,
      t,
      e
    ), l;
  }
  function ev(l, t, a, u) {
    return uu(), t.flags |= 256, Dl(l, t, a, u), t.child;
  }
  var cf = {
    dehydrated: null,
    treeContext: null,
    retryLane: 0,
    hydrationErrors: null
  };
  function ff(l) {
    return { baseLanes: l, cachePool: wo() };
  }
  function sf(l, t, a) {
    return l = l !== null ? l.childLanes & ~a : 0, t && (l |= yt), l;
  }
  function nv(l, t, a) {
    var u = t.pendingProps, e = !1, n = (t.flags & 128) !== 0, i;
    if ((i = n) || (i = l !== null && l.memoizedState === null ? !1 : (Xl.current & 2) !== 0), i && (e = !0, t.flags &= -129), i = (t.flags & 32) !== 0, t.flags &= -33, l === null) {
      if (L) {
        if (e ? Ra(t) : xa(), (l = hl) ? (l = Dd(
          l,
          Dt
        ), l = l !== null && l.data !== "&" ? l : null, l !== null && (t.memoizedState = {
          dehydrated: l,
          treeContext: Na !== null ? { id: Lt, overflow: Kt } : null,
          retryLane: 536870912,
          hydrationErrors: null
        }, a = Bo(l), a.return = t, t.child = a, Rl = t, hl = null)) : l = null, l === null) throw Oa(t);
        return fs(l) ? t.lanes = 32 : t.lanes = 536870912, null;
      }
      return n = u.children, u = u.fallback, e ? (xa(), e = t.mode, n = ti(
        { mode: "hidden", children: n },
        e
      ), u = au(
        u,
        e,
        a,
        null
      ), n.return = t, u.return = t, n.sibling = u, t.child = n, u = t.child, u.memoizedState = ff(a), u.childLanes = sf(
        l,
        i,
        a
      ), t.memoizedState = cf, Be(null, u)) : (Ra(t), of(t, n));
    }
    var c = l.memoizedState;
    if (c !== null) {
      var f = c.dehydrated;
      if (f !== null)
        return Gh(
          l,
          t,
          n,
          i,
          u,
          f,
          c,
          a
        );
    }
    return e ? (xa(), e = u.fallback, n = t.mode, c = l.child, f = c.sibling, u = ca(c, {
      mode: "hidden",
      children: u.children
    }), u.subtreeFlags = c.subtreeFlags & 1206910976, f !== null ? e = ca(f, e) : (e = au(
      e,
      n,
      a,
      null
    ), e.flags |= 2), e.return = t, u.return = t, u.sibling = e, t.child = u, Be(null, u), u = t.child, e = l.child.memoizedState, e === null ? e = ff(a) : (n = e.cachePool, n !== null ? (c = zl._currentValue, n = n.parent !== c ? { parent: c, pool: c } : n) : n = wo(), e = {
      baseLanes: e.baseLanes | a,
      cachePool: n
    }), u.memoizedState = e, u.childLanes = sf(
      l,
      i,
      a
    ), t.memoizedState = cf, Be(l.child, u)) : (Ra(t), a = l.child, l = a.sibling, a = ca(a, {
      mode: "visible",
      children: u.children
    }), a.return = t, a.sibling = null, l !== null && (i = t.deletions, i === null ? (t.deletions = [l], t.flags |= 16) : i.push(l)), t.child = a, t.memoizedState = null, a);
  }
  function of(l, t) {
    return t = ti(
      { mode: "visible", children: t },
      l.mode
    ), t.return = l, l.child = t;
  }
  function ti(l, t) {
    return l = Il(22, l, null, t), l.lanes = 0, l;
  }
  function ai(l, t, a) {
    return vu(t, l.child, null, a), l = of(
      t,
      t.pendingProps.children
    ), l.flags |= 2, t.memoizedState = null, l;
  }
  function Gh(l, t, a, u, e, n, i, c) {
    if (a)
      return t.flags & 256 ? (Ra(t), t.flags &= -257, ai(
        l,
        t,
        c
      )) : t.memoizedState !== null ? (xa(), t.child = l.child, t.flags |= 128, null) : (xa(), n = e.fallback, i = t.mode, e = ti(
        { mode: "visible", children: e.children },
        i
      ), n = au(
        n,
        i,
        c,
        null
      ), n.flags |= 2, e.return = t, n.return = t, e.sibling = n, t.child = e, vu(t, l.child, null, c), e = t.child, e.memoizedState = ff(c), e.childLanes = sf(
        l,
        u,
        c
      ), t.memoizedState = cf, Be(null, e));
    if (Ra(t), fs(n)) {
      if (u = n.nextSibling && n.nextSibling.dataset, u) var f = u.dgst;
      return u = f, u !== "" && (e = Error(r(419)), e.stack = "", e.digest = u, Ae({ value: e, source: null, stack: null })), ai(
        l,
        t,
        c
      );
    }
    if (_l || nu(l, t, c, !1), u = (c & l.childLanes) !== 0, _l || u) {
      if (Ca.current !== null)
        return ai(
          l,
          t,
          c
        );
      if (u = vl, u !== null && (e = Xs(
        u,
        c
      ), e !== 0 && e !== i.retryLane))
        throw i.retryLane = e, tu(l, e), at(u, l, e), ef;
      return cs(n) || Ei(), ai(
        l,
        t,
        c
      );
    }
    return cs(n) ? (t.flags |= 192, t.child = l.child, null) : (l = i.treeContext, hl = jt(n.nextSibling), Rl = t, L = !0, _a = null, Dt = !1, l !== null && Qo(t, l), t = of(
      t,
      e.children
    ), t.flags |= 134221824, t);
  }
  function iv(l, t, a) {
    l.lanes |= t;
    var u = l.alternate;
    u !== null && (u.lanes |= t), Hn(l.return, t, a);
  }
  function cv(l) {
    for (var t = null; l !== null; ) {
      var a = l.alternate;
      a !== null && Zn(a) === null && (t = l), l = l.sibling;
    }
    return t;
  }
  function ui(l, t, a, u, e, n) {
    var i = l.memoizedState;
    i === null ? l.memoizedState = {
      isBackwards: t,
      rendering: null,
      renderingStartTime: 0,
      last: u,
      tail: a,
      tailMode: e,
      treeForkCount: n
    } : (i.isBackwards = t, i.rendering = null, i.renderingStartTime = 0, i.last = u, i.tail = a, i.tailMode = e, i.treeForkCount = n);
  }
  function vf(l) {
    var t = l.child;
    for (l.child = null; t !== null; ) {
      var a = t.sibling;
      t.sibling = l.child, l.child = t, t = a;
    }
  }
  function df(l, t, a) {
    var u = t.pendingProps, e = u.revealOrder, n = u.tail;
    u = u.children;
    var i = Xl.current;
    if (t.flags & 128)
      return He(t, i), null;
    var c = (i & 2) !== 0;
    if (c ? (i = i & 1 | 2, t.flags |= 128) : i &= 1, He(t, i), e === "backwards" && l !== null ? (vf(l), Dl(l, t, u, a), vf(l)) : Dl(l, t, u, a), u = L ? Oe : 0, !c && l !== null && (l.flags & 128) !== 0)
      l: for (l = t.child; l !== null; ) {
        if (l.tag === 13)
          l.memoizedState !== null && iv(l, a, t);
        else if (l.tag === 19)
          iv(l, a, t);
        else if (l.child !== null) {
          l.child.return = l, l = l.child;
          continue;
        }
        if (l === t) break l;
        for (; l.sibling === null; ) {
          if (l.return === null || l.return === t)
            break l;
          l = l.return;
        }
        l.sibling.return = l.return, l = l.sibling;
      }
    switch (e) {
      case "backwards":
        a = cv(t.child), a === null ? (e = t.child, t.child = null) : (e = a.sibling, a.sibling = null, vf(t)), ui(
          t,
          !0,
          e,
          null,
          n,
          u
        );
        break;
      case "unstable_legacy-backwards":
        for (a = null, e = t.child, t.child = null; e !== null; ) {
          if (l = e.alternate, l !== null && Zn(l) === null) {
            t.child = e;
            break;
          }
          l = e.sibling, e.sibling = a, a = e, e = l;
        }
        ui(
          t,
          !0,
          a,
          null,
          n,
          u
        );
        break;
      case "together":
        ui(
          t,
          !1,
          null,
          null,
          void 0,
          u
        );
        break;
      case "independent":
        t.memoizedState = null;
        break;
      default:
        a = cv(t.child), a === null ? (e = t.child, t.child = null) : (e = a.sibling, a.sibling = null), ui(
          t,
          !1,
          e,
          a,
          n,
          u
        );
    }
    return t.child;
  }
  function fv(l, t, a) {
    var u = t.pendingProps;
    return Aa(t, t.type, u.value), Dl(l, t, u.children, a), t.child;
  }
  function ma(l, t, a) {
    if (l !== null && (t.dependencies = l.dependencies), qa |= t.lanes, (a & t.childLanes) === 0)
      if (l !== null) {
        if (nu(
          l,
          t,
          a,
          !1
        ), (a & t.childLanes) === 0)
          return null;
      } else return null;
    if (l !== null && t.child !== l.child)
      throw Error(r(153));
    if (t.child !== null) {
      for (l = t.child, a = ca(l, l.pendingProps), t.child = a, a.return = t; l.sibling !== null; )
        l = l.sibling, a = a.sibling = ca(l, l.pendingProps), a.return = t;
      a.sibling = null;
    }
    return t.child;
  }
  function mf(l, t) {
    return (l.lanes & t) !== 0 ? !0 : (l = l.dependencies, !!(l !== null && pn(l)));
  }
  function Xh(l, t, a) {
    switch (t.tag) {
      case 3:
        fn(t, t.stateNode.containerInfo), Aa(t, zl, l.memoizedState.cache), uu();
        break;
      case 27:
      case 5:
        Gi(t);
        break;
      case 4:
        fn(t, t.stateNode.containerInfo);
        break;
      case 10:
        Aa(
          t,
          t.type,
          t.memoizedProps.value
        );
        break;
      case 31:
        if (t.memoizedState !== null)
          return t.flags |= 128, qc(t), null;
        break;
      case 13:
        var u = t.memoizedState;
        if (u !== null) {
          if (u.dehydrated !== null)
            return Ra(t), t.flags |= 128, null;
          u = nu(
            l,
            t,
            a,
            !1
          );
          var e = t.child.childLanes;
          return u || (a & e) !== 0 ? nv(l, t, a) : (Ra(t), l = ma(
            l,
            t,
            a
          ), l !== null ? l.sibling : null);
        }
        Ra(t);
        break;
      case 19:
        if (t.flags & 128)
          return df(
            l,
            t,
            a
          );
        if (e = (l.flags & 128) !== 0, u = (a & t.childLanes) !== 0, u || (nu(
          l,
          t,
          a,
          !1
        ), u = (a & t.childLanes) !== 0), e) {
          if (u)
            return df(
              l,
              t,
              a
            );
          t.flags |= 128;
        }
        if (e = t.memoizedState, e !== null && (e.rendering = null, e.tail = null, e.lastEffect = null), He(t, Xl.current), u) break;
        return null;
      case 22:
        return t.lanes = 0, P0(
          l,
          t,
          a,
          t.pendingProps
        );
      case 24:
        Aa(t, zl, l.memoizedState.cache);
    }
    return ma(l, t, a);
  }
  function sv(l, t, a) {
    if (l !== null)
      if (l.memoizedProps !== t.pendingProps)
        _l = !0;
      else {
        if (!mf(l, a) && (t.flags & 128) === 0)
          return _l = !1, Xh(
            l,
            t,
            a
          );
        _l = (l.flags & 131072) !== 0;
      }
    else
      _l = !1, L && (t.flags & 1048576) !== 0 && Xo(t, Oe, t.index);
    switch (t.lanes = 0, t.tag) {
      case 16:
        l: {
          var u = t.pendingProps;
          if (l = su(t.elementType), t.type = l, typeof l == "function")
            Tc(l) ? (u = mu(l, u), t.tag = 1, t = uv(
              null,
              t,
              l,
              u,
              a
            )) : (t.tag = 0, t = nf(
              null,
              t,
              l,
              u,
              a
            ));
          else {
            if (l != null) {
              var e = l.$$typeof;
              if (e === O) {
                t.tag = 11, t = W0(
                  null,
                  t,
                  l,
                  u,
                  a
                );
                break l;
              } else if (e === ol) {
                t.tag = 14, t = k0(
                  null,
                  t,
                  l,
                  u,
                  a
                );
                break l;
              } else if (e === Ul) {
                t.tag = 10, t.type = l, t = fv(
                  null,
                  t,
                  a
                );
                break l;
              }
            }
            throw t = P(l) || l, Error(r(306, t, ""));
          }
        }
        return t;
      case 0:
        return nf(
          l,
          t,
          t.type,
          t.pendingProps,
          a
        );
      case 1:
        return u = t.type, e = mu(
          u,
          t.pendingProps
        ), uv(
          l,
          t,
          u,
          e,
          a
        );
      case 3:
        l: {
          if (fn(
            t,
            t.stateNode.containerInfo
          ), l === null) throw Error(r(387));
          u = t.pendingProps;
          var n = t.memoizedState;
          e = n.element, Rc(l, t), xe(t, u, null, a);
          var i = t.memoizedState;
          if (u = i.cache, Aa(t, zl, u), u !== n.cache && Ac(
            t,
            [zl],
            a,
            !0
          ), Re(), u = i.element, n.isDehydrated)
            if (n = {
              element: u,
              isDehydrated: !1,
              cache: i.cache
            }, t.updateQueue.baseState = n, t.memoizedState = n, t.flags & 256) {
              t = ev(
                l,
                t,
                u,
                a
              );
              break l;
            } else if (u !== e) {
              e = _t(
                Error(r(424)),
                t
              ), Ae(e), t = ev(
                l,
                t,
                u,
                a
              );
              break l;
            } else {
              switch (l = t.stateNode.containerInfo, l.nodeType) {
                case 9:
                  l = l.body;
                  break;
                default:
                  l = l.nodeName === "HTML" ? l.ownerDocument.body : l;
              }
              for (hl = jt(l.firstChild), Rl = t, L = !0, _a = null, Dt = !0, a = Po(
                t,
                null,
                u,
                a
              ), t.child = a; a; )
                a.flags = a.flags & -3 | 134221824, a = a.sibling;
            }
          else {
            if (uu(), u === e) {
              t = ma(
                l,
                t,
                a
              );
              break l;
            }
            Dl(l, t, u, a);
          }
          t = t.child;
        }
        return t;
      case 26:
        return Zu(l, t), l === null ? (a = Hd(
          t.type,
          null,
          t.pendingProps,
          null
        )) ? t.memoizedState = a : L || (t.stateNode = md(
          t.type,
          t.pendingProps,
          Sa.current,
          t
        )) : t.memoizedState = Hd(
          t.type,
          l.memoizedProps,
          t.pendingProps,
          l.memoizedState
        ), null;
      case 27:
        return Gi(t), l === null && L && (u = t.stateNode = Ud(
          t.type,
          t.pendingProps,
          Sa.current
        ), Rl = t, Dt = !0, e = hl, Qa(t.type) ? (ss = e, hl = jt(u.firstChild)) : hl = e), Dl(
          l,
          t,
          t.pendingProps.children,
          a
        ), Zu(l, t), l === null && (t.flags |= 4194304), t.child;
      case 5:
        return l === null && L && ((e = u = hl) && (u = Hr(
          u,
          t.type,
          t.pendingProps,
          Dt
        ), u !== null ? (t.stateNode = u, Rl = t, hl = jt(u.firstChild), Dt = !1, e = !0) : e = !1), e || Oa(t)), Gi(t), e = t.type, n = t.pendingProps, i = l !== null ? l.memoizedProps : null, u = n.children, ls(e, n) ? u = null : i !== null && ls(e, i) && (t.flags |= 32), t.memoizedState !== null && (e = Xc(
          l,
          t,
          jh,
          null,
          null,
          a
        ), ce._currentValue = e), Zu(l, t), Dl(l, t, u, a), t.child;
      case 6:
        return l === null && L && ((l = a = hl) && (a = pr(
          a,
          t.pendingProps,
          Dt
        ), a !== null ? (t.stateNode = a, Rl = t, hl = null, l = !0) : l = !1), l || Oa(t)), null;
      case 13:
        return nv(l, t, a);
      case 4:
        return fn(
          t,
          t.stateNode.containerInfo
        ), u = t.pendingProps, l === null ? t.child = vu(
          t,
          null,
          u,
          a
        ) : Dl(l, t, u, a), t.child;
      case 11:
        return W0(
          l,
          t,
          t.type,
          t.pendingProps,
          a
        );
      case 7:
        return u = t.pendingProps, Zu(l, t), Dl(l, t, u, a), t.child;
      case 8:
        return Dl(
          l,
          t,
          t.pendingProps.children,
          a
        ), t.child;
      case 12:
        return Dl(
          l,
          t,
          t.pendingProps.children,
          a
        ), t.child;
      case 10:
        return fv(l, t, a);
      case 9:
        return e = t.type._context, u = t.pendingProps.children, iu(t), e = Bl(e), u = u(e), t.flags |= 1, Dl(l, t, u, a), t.child;
      case 14:
        return k0(
          l,
          t,
          t.type,
          t.pendingProps,
          a
        );
      case 15:
        return I0(
          l,
          t,
          t.type,
          t.pendingProps,
          a
        );
      case 19:
        return df(l, t, a);
      case 31:
        return Bh(l, t, a);
      case 22:
        return P0(
          l,
          t,
          a,
          t.pendingProps
        );
      case 24:
        return iu(t), u = Bl(zl), l === null ? (e = jc(), e === null && (e = vl, n = Dc(), e.pooledCache = n, n.refCount++, n !== null && (e.pooledCacheLanes |= a), e = n), t.memoizedState = { parent: u, cache: e }, Cc(t), Aa(t, zl, e)) : ((l.lanes & a) !== 0 && (Rc(l, t), xe(t, null, null, a), Re()), e = l.memoizedState, n = t.memoizedState, e.parent !== u ? (e = { parent: u, cache: u }, t.memoizedState = e, t.lanes === 0 && (t.memoizedState = t.updateQueue.baseState = e), Aa(t, zl, u)) : (u = n.cache, Aa(t, zl, u), u !== e.cache && Ac(
          t,
          [zl],
          a,
          !0
        ))), Dl(
          l,
          t,
          t.pendingProps.children,
          a
        ), t.child;
      case 30:
        return t.stateNode === null && (t.stateNode = {
          autoName: null,
          paired: null,
          clones: null,
          ref: null
        }), u = t.pendingProps, u.name != null && u.name !== "auto" ? t.flags |= l === null ? 18882560 : 18874368 : L && Rn(t), l !== null && l.memoizedProps.name !== u.name ? t.flags |= 4194816 : Zu(l, t), Dl(l, t, u.children, a), t.child;
      case 29:
        throw t.pendingProps;
    }
    throw Error(r(156, t.tag));
  }
  function ha(l) {
    l.flags |= 4;
  }
  function hf(l, t, a, u, e) {
    var n;
    if ((n = (l.mode & 32) !== 0) && (n = a === null ? Bd(t, u) : Bd(t, u) && (u.src !== a.src || u.srcSet !== a.srcSet)), n) {
      if (l.flags |= 16777216, (e & 335544128) === e)
        if (l.stateNode.complete) l.flags |= 8192;
        else if (Lv()) l.flags |= 8192;
        else
          throw ou = Gn, Uc;
    } else l.flags &= -16777217;
  }
  function ov(l, t) {
    if (t.type !== "stylesheet" || (t.state.loading & 4) !== 0)
      l.flags &= -16777217;
    else if (l.flags |= 16777216, !Gd(t))
      if (Lv()) l.flags |= 8192;
      else
        throw ou = Gn, Uc;
  }
  function ei(l, t) {
    t !== null && (l.flags |= 4), l.flags & 16384 && (t = l.tag !== 22 ? qs() : 536870912, l.lanes |= t, Fu |= t);
  }
  function Ge(l, t) {
    if (!L)
      switch (l.tailMode) {
        case "visible":
          break;
        case "collapsed":
          for (var a = l.tail, u = null; a !== null; )
            a.alternate !== null && (u = a), a = a.sibling;
          u === null ? t || l.tail === null ? l.tail = null : l.tail.sibling = null : u.sibling = null;
          break;
        default:
          for (t = l.tail, a = null; t !== null; )
            t.alternate !== null && (a = t), t = t.sibling;
          a === null ? l.tail = null : a.sibling = null;
      }
  }
  function rl(l) {
    var t = l.alternate !== null && l.alternate.child === l.child, a = 0, u = 0;
    if (t)
      for (var e = l.child; e !== null; )
        a |= e.lanes | e.childLanes, u |= e.subtreeFlags & 1206910976, u |= e.flags & 1206910976, e.return = l, e = e.sibling;
    else
      for (e = l.child; e !== null; )
        a |= e.lanes | e.childLanes, u |= e.subtreeFlags, u |= e.flags, e.return = l, e = e.sibling;
    return l.subtreeFlags |= u, l.childLanes = a, t;
  }
  function Qh(l, t, a) {
    var u = t.pendingProps;
    switch (zc(t), t.tag) {
      case 16:
      case 15:
      case 0:
      case 11:
      case 7:
      case 8:
      case 12:
      case 9:
      case 14:
        return rl(t), null;
      case 1:
        return rl(t), null;
      case 3:
        return a = t.stateNode, u = null, l !== null && (u = l.memoizedState.cache), t.memoizedState.cache !== u && (t.flags |= 2048), oa(zl), Eu(), a.pendingContext && (a.context = a.pendingContext, a.pendingContext = null), (l === null || l.child === null) && (Yu(t) ? ha(t) : l === null || l.memoizedState.isDehydrated && (t.flags & 256) === 0 || (t.flags |= 1024, _c())), rl(t), null;
      case 26:
        var e = t.type, n = t.memoizedState;
        return l === null ? (ha(t), n !== null ? (rl(t), ov(t, n)) : (rl(t), hf(
          t,
          e,
          null,
          u,
          a
        ))) : n ? n !== l.memoizedState ? (ha(t), rl(t), ov(t, n)) : (rl(t), t.flags &= -16777217) : (l = l.memoizedProps, l !== u && ha(t), rl(t), hf(
          t,
          e,
          l,
          u,
          a
        )), null;
      case 27:
        if (sn(t), a = Sa.current, e = t.type, l !== null && t.stateNode != null)
          l.memoizedProps !== u && ha(t);
        else {
          if (!u) {
            if (t.stateNode === null)
              throw Error(r(166));
            return rl(t), t.subtreeFlags &= -33554433, null;
          }
          l = Vt.current, Yu(t) ? Vo(t) : (l = Ud(e, u, a), t.stateNode = l, ha(t));
        }
        return rl(t), t.subtreeFlags &= -33554433, null;
      case 5:
        if (sn(t), e = t.type, l !== null && t.stateNode != null)
          l.memoizedProps !== u && ha(t);
        else {
          if (!u) {
            if (t.stateNode === null)
              throw Error(r(166));
            return rl(t), t.subtreeFlags &= -33554433, null;
          }
          if (n = Vt.current, Yu(t))
            Vo(t);
          else {
            var i = We(
              Sa.current
            );
            switch (n) {
              case 1:
                n = i.createElementNS(
                  "http://www.w3.org/2000/svg",
                  e
                );
                break;
              case 2:
                n = i.createElementNS(
                  "http://www.w3.org/1998/Math/MathML",
                  e
                );
                break;
              default:
                switch (e) {
                  case "svg":
                    n = i.createElementNS(
                      "http://www.w3.org/2000/svg",
                      e
                    );
                    break;
                  case "math":
                    n = i.createElementNS(
                      "http://www.w3.org/1998/Math/MathML",
                      e
                    );
                    break;
                  case "script":
                    n = i.createElement("div"), n.innerHTML = "<script><\/script>", n = n.removeChild(
                      n.firstChild
                    );
                    break;
                  case "select":
                    n = typeof u.is == "string" ? i.createElement("select", {
                      is: u.is
                    }) : i.createElement("select"), u.multiple ? n.multiple = !0 : u.size && (n.size = u.size);
                    break;
                  default:
                    n = typeof u.is == "string" ? i.createElement(e, { is: u.is }) : i.createElement(e);
                }
            }
            n[ql] = t, n[kl] = u;
            l: for (i = t.child; i !== null; ) {
              if (i.tag === 5 || i.tag === 6)
                n.appendChild(i.stateNode);
              else if (i.tag !== 4 && i.tag !== 27 && i.child !== null) {
                i.child.return = i, i = i.child;
                continue;
              }
              if (i === t) break l;
              for (; i.sibling === null; ) {
                if (i.return === null || i.return === t)
                  break l;
                i = i.return;
              }
              i.sibling.return = i.return, i = i.sibling;
            }
            t.stateNode = n;
            l: switch (Vl(n, e, u), e) {
              case "button":
              case "input":
              case "select":
              case "textarea":
                u = !!u.autoFocus;
                break l;
              case "img":
                u = !0;
                break l;
              default:
                u = !1;
            }
            u && ha(t);
          }
        }
        return rl(t), t.subtreeFlags &= -33554433, hf(
          t,
          t.type,
          l === null ? null : l.memoizedProps,
          t.pendingProps,
          a
        ), null;
      case 6:
        if (l && t.stateNode != null)
          l.memoizedProps !== u && ha(t);
        else {
          if (typeof u != "string" && t.stateNode === null)
            throw Error(r(166));
          if (l = Sa.current, Yu(t)) {
            if (l = t.stateNode, a = t.memoizedProps, u = null, e = Rl, e !== null)
              switch (e.tag) {
                case 27:
                case 5:
                  u = e.memoizedProps;
              }
            l[ql] = t, l = !!(l.nodeValue === a || u !== null && u.suppressHydrationWarning === !0 || sd(l.nodeValue, a)), l || Oa(t, !0);
          } else
            l = We(l).createTextNode(
              u
            ), l[ql] = t, t.stateNode = l;
        }
        return rl(t), null;
      case 31:
        if (a = t.memoizedState, l === null || l.memoizedState !== null) {
          if (u = Yu(t), a !== null) {
            if (l === null) {
              if (!u) throw Error(r(318));
              if (l = t.memoizedState, l = l !== null ? l.dehydrated : null, !l) throw Error(r(557));
              l[ql] = t;
            } else
              uu(), (t.flags & 128) === 0 && (t.memoizedState = null), t.flags |= 4;
            rl(t), l = !1;
          } else
            a = _c(), l !== null && l.memoizedState !== null && (l.memoizedState.hydrationErrors = a), l = !0;
          if (!l)
            return t.flags & 256 ? (mt(t), t) : (mt(t), null);
          if ((t.flags & 128) !== 0)
            throw Error(r(558));
        }
        return rl(t), null;
      case 13:
        if (u = t.memoizedState, l === null || l.memoizedState !== null && l.memoizedState.dehydrated !== null) {
          if (e = Yu(t), u !== null && u.dehydrated !== null) {
            if (l === null) {
              if (!e) throw Error(r(318));
              if (e = t.memoizedState, e = e !== null ? e.dehydrated : null, !e) throw Error(r(317));
              e[ql] = t;
            } else
              uu(), (t.flags & 128) === 0 && (t.memoizedState = null), t.flags |= 4;
            rl(t), e = !1;
          } else
            e = _c(), l !== null && l.memoizedState !== null && (l.memoizedState.hydrationErrors = e), e = !0;
          if (!e)
            return t.flags & 256 ? (mt(t), t) : (mt(t), null);
        }
        return mt(t), (t.flags & 128) !== 0 ? (t.lanes = a, t) : (a = u !== null, l = l !== null && l.memoizedState !== null, a && (u = t.child, e = null, u.alternate !== null && u.alternate.memoizedState !== null && u.alternate.memoizedState.cachePool !== null && (e = u.alternate.memoizedState.cachePool.pool), n = null, u.memoizedState !== null && u.memoizedState.cachePool !== null && (n = u.memoizedState.cachePool.pool), n !== e && (u.flags |= 2048)), a !== l && a && (t.child.flags |= 8192), ei(t, t.updateQueue), rl(t), null);
      case 4:
        return Eu(), l === null && $f(t.stateNode.containerInfo), t.flags |= 67108864, rl(t), null;
      case 10:
        return oa(t.type), rl(t), null;
      case 19:
        if (Bc(t), u = t.memoizedState, u === null) return rl(t), null;
        if (e = (t.flags & 128) !== 0, n = u.rendering, n === null)
          if (e) Ge(u, !1);
          else {
            if (Tl !== 0 || l !== null && (l.flags & 128) !== 0)
              for (l = t.child; l !== null; ) {
                if (n = Zn(l), n !== null) {
                  for (t.flags |= 128, Ge(u, !1), l = n.updateQueue, t.updateQueue = l, ei(t, l), t.subtreeFlags = 0, l = a, a = t.child; a !== null; )
                    qo(a, l), a = a.sibling;
                  return He(
                    t,
                    Xl.current & 1 | 2
                  ), L && fa(t, u.treeForkCount), t.child;
                }
                l = l.sibling;
              }
            u.tail !== null && ft() > yi && (t.flags |= 128, e = !0, Ge(u, !1), t.lanes = 4194304);
          }
        else {
          if (!e)
            if (l = Zn(n), l !== null) {
              if (t.flags |= 128, e = !0, l = l.updateQueue, t.updateQueue = l, ei(t, l), Ge(u, !0), u.tail === null && u.tailMode !== "collapsed" && u.tailMode !== "visible" && !n.alternate && !L)
                return rl(t), null;
            } else
              2 * ft() - u.renderingStartTime > yi && a !== 536870912 && (t.flags |= 128, e = !0, Ge(u, !1), t.lanes = 4194304);
          u.isBackwards ? (n.sibling = t.child, t.child = n) : (l = u.last, l !== null ? l.sibling = n : t.child = n, u.last = n);
        }
        if (u.tail !== null) {
          l = u.tail;
          l: {
            for (a = l; a !== null; ) {
              if (a.alternate !== null) {
                a = !1;
                break l;
              }
              a = a.sibling;
            }
            a = !0;
          }
          return u.rendering = l, u.tail = l.sibling, u.renderingStartTime = ft(), l.sibling = null, n = Xl.current, n = e ? n & 1 | 2 : n & 1, u.tailMode === "visible" || u.tailMode === "collapsed" || !a || L ? He(t, n) : (a = n, ml(Gl, t), ml(Xl, a), Ll === null && (Ll = t)), L && fa(t, u.treeForkCount), l;
        }
        return rl(t), null;
      case 22:
      case 23:
        return mt(t), Yc(), u = t.memoizedState !== null, l !== null ? l.memoizedState !== null !== u && (t.flags |= 8192) : u && (t.flags |= 8192), u ? (a & 536870912) !== 0 && (t.flags & 128) === 0 && (rl(t), t.subtreeFlags & 6 && (t.flags |= 8192)) : rl(t), a = t.updateQueue, a !== null && ei(t, a.retryQueue), a = null, l !== null && l.memoizedState !== null && l.memoizedState.cachePool !== null && (a = l.memoizedState.cachePool.pool), u = null, t.memoizedState !== null && t.memoizedState.cachePool !== null && (u = t.memoizedState.cachePool.pool), u !== a && (t.flags |= 2048), l !== null && Yl(fu), null;
      case 24:
        return a = null, l !== null && (a = l.memoizedState.cache), t.memoizedState.cache !== a && (t.flags |= 2048), oa(zl), rl(t), null;
      case 25:
        return null;
      case 30:
        return t.flags |= 33554432, rl(t), null;
    }
    throw Error(r(156, t.tag));
  }
  function Vh(l, t) {
    switch (zc(t), t.tag) {
      case 1:
        return l = t.flags, l & 65536 ? (t.flags = l & -65537 | 128, t) : null;
      case 3:
        return oa(zl), Eu(), l = t.flags, (l & 65536) !== 0 && (l & 128) === 0 ? (t.flags = l & -65537 | 128, t) : null;
      case 26:
      case 27:
      case 5:
        return sn(t), null;
      case 31:
        if (t.memoizedState !== null) {
          if (mt(t), t.alternate === null)
            throw Error(r(340));
          uu();
        }
        return l = t.flags, l & 65536 ? (t.flags = l & -65537 | 128, t) : null;
      case 13:
        if (mt(t), l = t.memoizedState, l !== null && l.dehydrated !== null) {
          if (t.alternate === null)
            throw Error(r(340));
          uu();
        }
        return l = t.flags, l & 65536 ? (t.flags = l & -65537 | 128, t) : null;
      case 19:
        return Bc(t), l = t.flags, l & 65536 ? (t.flags = l & -65537 | 128, l = t.memoizedState, l !== null && (l.rendering = null, l.tail = null), t.flags |= 4, t) : null;
      case 4:
        return Eu(), null;
      case 10:
        return oa(t.type), null;
      case 22:
      case 23:
        return mt(t), Yc(), l !== null && Yl(fu), l = t.flags, l & 65536 ? (t.flags = l & -65537 | 128, t) : null;
      case 24:
        return oa(zl), null;
      case 25:
        return null;
      default:
        return null;
    }
  }
  function vv(l, t) {
    switch (zc(t), t.tag) {
      case 3:
        oa(zl), Eu();
        break;
      case 26:
      case 27:
      case 5:
        sn(t);
        break;
      case 4:
        Eu();
        break;
      case 31:
        t.memoizedState !== null && mt(t);
        break;
      case 13:
        mt(t);
        break;
      case 19:
        Bc(t);
        break;
      case 10:
        oa(t.type);
        break;
      case 22:
      case 23:
        mt(t), Yc(), l !== null && Yl(fu);
        break;
      case 24:
        oa(zl);
    }
  }
  function Xe(l, t) {
    try {
      var a = t.updateQueue, u = a !== null ? a.lastEffect : null;
      if (u !== null) {
        var e = u.next;
        a = e;
        do {
          if ((a.tag & l) === l) {
            u = void 0;
            var n = a.create, i = a.inst;
            u = n(), i.destroy = u;
          }
          a = a.next;
        } while (a !== e);
      }
    } catch (c) {
      cl(t, t.return, c);
    }
  }
  function Ha(l, t, a) {
    try {
      var u = t.updateQueue, e = u !== null ? u.lastEffect : null;
      if (e !== null) {
        var n = e.next;
        u = n;
        do {
          if ((u.tag & l) === l) {
            var i = u.inst, c = i.destroy;
            if (c !== void 0) {
              i.destroy = void 0, e = t;
              var f = a, h = c;
              try {
                h();
              } catch (S) {
                cl(
                  e,
                  f,
                  S
                );
              }
            }
          }
          u = u.next;
        } while (u !== n);
      }
    } catch (S) {
      cl(t, t.return, S);
    }
  }
  function dv(l) {
    var t = l.updateQueue;
    if (t !== null) {
      var a = l.stateNode;
      try {
        t0(t, a);
      } catch (u) {
        cl(l, l.return, u);
      }
    }
  }
  function mv(l, t, a) {
    a.props = mu(
      l.type,
      l.memoizedProps
    ), a.state = l.memoizedState;
    try {
      a.componentWillUnmount();
    } catch (u) {
      cl(l, t, u);
    }
  }
  function Jt(l, t) {
    try {
      var a = l.ref;
      if (a !== null) {
        switch (l.tag) {
          case 26:
          case 27:
          case 5:
            var u = l.stateNode;
            break;
          case 30:
            var e = l.stateNode, n = na(l.memoizedProps, e);
            (e.ref === null || e.ref.name !== n) && (e.ref = Ed(n)), u = e.ref;
            break;
          case 7:
            if (l.stateNode === null) {
              var i = new St(l);
              E(
                l.child,
                !1,
                Rr,
                i,
                void 0,
                void 0
              ), l.stateNode = i;
            }
            u = l.stateNode;
            break;
          default:
            u = l.stateNode;
        }
        typeof a == "function" ? l.refCleanup = a(u) : a.current = u;
      }
    } catch (c) {
      cl(l, t, c);
    }
  }
  function Ql(l, t) {
    var a = l.ref, u = l.refCleanup;
    if (a !== null)
      if (typeof u == "function")
        try {
          u();
        } catch (e) {
          cl(l, t, e);
        } finally {
          l.refCleanup = null, l = l.alternate, l != null && (l.refCleanup = null);
        }
      else if (typeof a == "function")
        try {
          a(null);
        } catch (e) {
          cl(l, t, e);
        }
      else a.current = null;
  }
  function ni(l, t) {
    if ((l.tag === 5 || l.tag === 27 || l.tag === 6) && l.alternate === null && t !== null)
      for (var a = 0; a < t.length; a++)
        Ad(
          l.stateNode,
          t[a]
        );
  }
  function hv(l) {
    for (var t = l.return; t !== null && (yf(t) && Ad(l.stateNode, t.stateNode), !rf(t)); )
      t = t.return;
  }
  function Qe(l) {
    for (var t = l.return; t !== null && (yf(t) && xr(l.stateNode, t.stateNode), !rf(t)); )
      t = t.return;
  }
  function rf(l) {
    return l.tag === 5 || l.tag === 3 || l.tag === 27;
  }
  function yf(l) {
    return l && l.tag === 7 && l.stateNode !== null;
  }
  function gf(l) {
    var t = l.type, a = l.memoizedProps, u = l.stateNode;
    try {
      l: switch (t) {
        case "button":
        case "input":
        case "select":
        case "textarea":
          a.autoFocus && u.focus();
          break l;
        case "img":
          a.src ? u.src = a.src : a.srcSet && (u.srcset = a.srcSet);
      }
    } catch (e) {
      cl(l, l.return, e);
    }
  }
  function Sf(l, t, a) {
    try {
      var u = l.stateNode;
      hr(u, l.type, a, t), u[kl] = t;
    } catch (e) {
      cl(l, l.return, e);
    }
  }
  function rv(l) {
    return l.tag === 5 || l.tag === 3 || l.tag === 26 || l.tag === 27 && Qa(l.type) || l.tag === 4;
  }
  function Tf(l) {
    l: for (; ; ) {
      for (; l.sibling === null; ) {
        if (l.return === null || rv(l.return)) return null;
        l = l.return;
      }
      for (l.sibling.return = l.return, l = l.sibling; l.tag !== 5 && l.tag !== 6 && l.tag !== 18; ) {
        if (l.tag === 27 && Qa(l.type) || l.flags & 2 || l.child === null || l.tag === 4) continue l;
        l.child.return = l, l = l.child;
      }
      if (!(l.flags & 2)) return l.stateNode;
    }
  }
  function Ef(l, t, a, u) {
    var e = l.tag;
    if (e === 5 || e === 6)
      e = l.stateNode, t ? (a.nodeType === 9 ? a.body : a.nodeName === "HTML" ? a.ownerDocument.body : a).insertBefore(e, t) : (t = a.nodeType === 9 ? a.body : a.nodeName === "HTML" ? a.ownerDocument.body : a, t.appendChild(e), a = a._reactRootContainer, a != null || t.onclick !== null || (t.onclick = Zt)), ni(l, u), ll = !0;
    else if (e !== 4 && (e === 27 && (ni(l, u), u = null, Qa(l.type) && (a = l.stateNode, t = null)), l = l.child, l !== null))
      for (Ef(
        l,
        t,
        a,
        u
      ), l = l.sibling; l !== null; )
        Ef(
          l,
          t,
          a,
          u
        ), l = l.sibling;
  }
  function ii(l, t, a, u) {
    var e = l.tag;
    if (e === 5 || e === 6)
      e = l.stateNode, t ? a.insertBefore(e, t) : a.appendChild(e), ni(l, u), ll = !0;
    else if (e !== 4 && (e === 27 && (ni(l, u), u = null, Qa(l.type) && (a = l.stateNode)), l = l.child, l !== null))
      for (ii(
        l,
        t,
        a,
        u
      ), l = l.sibling; l !== null; )
        ii(
          l,
          t,
          a,
          u
        ), l = l.sibling;
  }
  function yv(l) {
    var t = l.stateNode, a = l.memoizedProps;
    try {
      for (var u = l.type, e = t.attributes; e.length; )
        t.removeAttributeNode(e[0]);
      Vl(t, u, a), t[ql] = l, t[kl] = a;
    } catch (n) {
      cl(l, l.return, n);
    }
  }
  var ci = !1, ht = null;
  function gv(l) {
    (l.tag === 30 || (l.subtreeFlags & 33554432) !== 0) && (ci = !0);
  }
  var wt = null;
  function Sv() {
    var l = wt;
    return wt = null, l;
  }
  var Pl = 0;
  function Lu(l, t, a, u, e) {
    return Pl = 0, Tv(
      l.child,
      t,
      a,
      u,
      e
    );
  }
  function Tv(l, t, a, u, e) {
    for (var n = !1; l !== null; ) {
      if (l.tag === 5) {
        var i = l.stateNode;
        if (u !== null) {
          var c = us(i);
          u.push(c), c.view && (n = !0);
        } else
          n || us(i).view && (n = !0);
        ci = !0, Sd(
          i,
          Pl === 0 ? t : t + "_" + Pl,
          a
        ), Pl++;
      } else (l.tag !== 22 || l.memoizedState === null) && (l.tag === 30 && e || Tv(
        l.child,
        t,
        a,
        u,
        e
      ) && (n = !0));
      l = l.sibling;
    }
    return n;
  }
  function Ft(l, t) {
    for (; l !== null; )
      l.tag === 5 ? Td(l.stateNode, l.memoizedProps) : (l.tag !== 22 || l.memoizedState === null) && (l.tag === 30 && t || Ft(
        l.child,
        t
      )), l = l.sibling;
  }
  function fi(l) {
    if ((l.subtreeFlags & 18874368) !== 0)
      for (l = l.child; l !== null; ) {
        if ((l.tag !== 22 || l.memoizedState === null) && (fi(l), l.tag === 30 && (l.flags & 18874368) !== 0 && l.stateNode.paired)) {
          var t = l.memoizedProps;
          if (t.name == null || t.name === "auto")
            throw Error(r(544));
          var a = t.name;
          t = ia(t.default, t.share), t !== "none" && (Lu(
            l,
            a,
            t,
            null,
            !1
          ) || Ft(l.child, !1));
        }
        l = l.sibling;
      }
  }
  function bf(l, t) {
    if (l.tag === 30) {
      var a = l.stateNode, u = l.memoizedProps, e = na(u, a), n = ia(
        u.default,
        a.paired ? u.share : u.enter
      );
      n !== "none" ? Lu(l, e, n, null, !1) ? (fi(l), a.paired || t || Iu(l, u.onEnter)) : Ft(l.child, !1) : fi(l);
    } else if ((l.subtreeFlags & 33554432) !== 0)
      for (l = l.child; l !== null; )
        bf(l, t), l = l.sibling;
    else fi(l);
  }
  function zf(l) {
    if (ht !== null && ht.size !== 0) {
      var t = ht;
      if ((l.subtreeFlags & 18874368) !== 0)
        for (l = l.child; l !== null; ) {
          if (l.tag !== 22 || l.memoizedState === null) {
            if (l.tag === 30 && (l.flags & 18874368) !== 0) {
              var a = l.memoizedProps, u = a.name;
              if (u != null && u !== "auto") {
                var e = t.get(u);
                if (e !== void 0) {
                  var n = ia(
                    a.default,
                    a.share
                  );
                  if (n !== "none" && (Lu(
                    l,
                    u,
                    n,
                    null,
                    !1
                  ) ? (n = l.stateNode, e.paired = n, n.paired = e, Iu(l, a.onShare)) : Ft(l.child, !1)), t.delete(u), t.size === 0) break;
                }
              }
            }
            zf(l);
          }
          l = l.sibling;
        }
    }
  }
  function Nf(l) {
    if (l.tag === 30) {
      var t = l.memoizedProps, a = na(t, l.stateNode), u = ht !== null ? ht.get(a) : void 0, e = ia(
        t.default,
        u !== void 0 ? t.share : t.exit
      );
      e !== "none" && (Lu(l, a, e, null, !1) ? u !== void 0 ? (e = l.stateNode, u.paired = e, e.paired = u, ht.delete(a), Iu(l, t.onShare)) : Iu(l, t.onExit) : Ft(l.child, !1)), ht !== null && zf(l);
    } else if ((l.subtreeFlags & 33554432) !== 0)
      for (l = l.child; l !== null; )
        Nf(l), l = l.sibling;
    else
      ht !== null && zf(l);
  }
  function Ev(l) {
    for (l = l.child; l !== null; ) {
      if (l.tag === 30) {
        var t = l.memoizedProps, a = na(t, l.stateNode);
        t = ia(t.default, t.update), l.flags &= -5, t !== "none" && Lu(
          l,
          a,
          t,
          l.memoizedState = [],
          !1
        );
      } else
        (l.subtreeFlags & 33554432) !== 0 && Ev(l);
      l = l.sibling;
    }
  }
  function _f(l) {
    if ((l.subtreeFlags & 18874368) !== 0)
      for (l = l.child; l !== null; ) {
        if (l.tag !== 22 || l.memoizedState === null) {
          if (l.tag === 30 && (l.flags & 18874368) !== 0) {
            var t = l.stateNode;
            t.paired !== null && (t.paired = null, Ft(l.child, !1));
          }
          _f(l);
        }
        l = l.sibling;
      }
  }
  function si(l) {
    if (l.tag === 30)
      l.stateNode.paired = null, Ft(l.child, !1), _f(l);
    else if ((l.subtreeFlags & 33554432) !== 0)
      for (l = l.child; l !== null; )
        si(l), l = l.sibling;
    else _f(l);
  }
  function bv(l) {
    for (l = l.child; l !== null; )
      l.tag === 30 ? Ft(l.child, !1) : (l.subtreeFlags & 33554432) !== 0 && bv(l), l = l.sibling;
  }
  function Of(l, t, a, u, e, n, i) {
    for (var c = !1; t !== null; ) {
      if (t.tag === 5) {
        var f = t.stateNode;
        if (n !== null && Pl < n.length) {
          var h = n[Pl], S = us(f);
          (h.view || S.view) && (c = !0);
          var b;
          if (b = (l.flags & 4) === 0)
            if (S.clip) b = !0;
            else {
              b = h.rect;
              var d = S.rect;
              b = b.y !== d.y || b.x !== d.x || b.height !== d.height || b.width !== d.width;
            }
          b && (l.flags |= 4), S.abs ? S = !h.abs : (h = h.rect, S = S.rect, S = h.height !== S.height || h.width !== S.width), S && (l.flags |= 32);
        } else l.flags |= 32;
        (l.flags & 4) !== 0 && Sd(
          f,
          Pl === 0 ? a : a + "_" + Pl,
          e
        ), c && (l.flags & 4) !== 0 || (wt === null && (wt = []), wt.push(
          f,
          Pl === 0 ? u : u + "_" + Pl,
          t.memoizedProps
        )), Pl++;
      } else (t.tag !== 22 || t.memoizedState === null) && (t.tag === 30 && i ? l.flags |= t.flags & 32 : Of(
        l,
        t.child,
        a,
        u,
        e,
        n,
        i
      ) && (c = !0));
      t = t.sibling;
    }
    return c;
  }
  function zv(l, t) {
    for (l = l.child; l !== null; ) {
      if (l.tag === 30) {
        var a = l.memoizedProps, u = l.stateNode, e = na(a, u), n = ia(a.default, a.update), i;
        i = l.memoizedState, l.memoizedState = null, u = l;
        var c = l.child;
        Pl = 0, e = Of(
          u,
          c,
          e,
          e,
          n,
          i,
          !1
        ), (l.flags & 4) !== 0 && e && Iu(l, a.onUpdate);
      } else
        (l.subtreeFlags & 33554432) !== 0 && zv(l);
      l = l.sibling;
    }
  }
  var xl = !1, nl = !1, $t = !1, Af = !1, Nv = typeof WeakSet == "function" ? WeakSet : Set, Hl = null, Wt = !1, Ve = !1, oi = !1, Df = !1;
  function Zh(l, t, a) {
    if (l = l.containerInfo, If = fe, l = Do(l), dc(l)) {
      if ("selectionStart" in l)
        var u = {
          start: l.selectionStart,
          end: l.selectionEnd
        };
      else
        l: {
          u = (u = l.ownerDocument) && u.defaultView || window;
          var e = u.getSelection && u.getSelection();
          if (e && e.rangeCount !== 0) {
            u = e.anchorNode;
            var n = e.anchorOffset, i = e.focusNode;
            e = e.focusOffset;
            try {
              u.nodeType, i.nodeType;
            } catch {
              u = null;
              break l;
            }
            var c = 0, f = -1, h = -1, S = 0, b = 0, d = l, g = null;
            t: for (; ; ) {
              for (var _; d !== u || n !== 0 && d.nodeType !== 3 || (f = c + n), d !== i || e !== 0 && d.nodeType !== 3 || (h = c + e), d.nodeType === 3 && (c += d.nodeValue.length), (_ = d.firstChild) !== null; )
                g = d, d = _;
              for (; ; ) {
                if (d === l) break t;
                if (g === u && ++S === n && (f = c), g === i && ++b === e && (h = c), (_ = d.nextSibling) !== null) break;
                d = g, g = d.parentNode;
              }
              d = _;
            }
            u = f === -1 || h === -1 ? null : { start: f, end: h };
          } else u = null;
        }
      u = u || { start: 0, end: 0 };
    } else u = null;
    for (Pf = { focusedElem: l, selectionRange: u }, fe = !1, a = (a & 335544064) === a, Hl = t, t = a ? 9270 : 1024; Hl !== null; ) {
      if (l = Hl, a && (u = l.deletions, u !== null))
        for (n = 0; n < u.length; n++)
          a && Nf(u[n]);
      if (l.alternate === null && (l.flags & 2) !== 0)
        a && gv(l), vi(a);
      else {
        if (l.tag === 22) {
          if (u = l.alternate, l.memoizedState !== null) {
            u !== null && u.memoizedState === null && a && Nf(u), vi(a);
            continue;
          } else if (u !== null && u.memoizedState !== null) {
            a && gv(l), vi(a);
            continue;
          }
        }
        u = l.child, (l.subtreeFlags & t) !== 0 && u !== null ? (u.return = l, Hl = u) : (a && Ev(l), vi(a));
      }
    }
    ht = null;
  }
  function vi(l) {
    for (; Hl !== null; ) {
      var t = Hl, a = l, u = t.alternate, e = t.flags;
      switch (t.tag) {
        case 0:
        case 11:
        case 15:
          break;
        case 1:
          if ((e & 1024) !== 0 && u !== null) {
            a = void 0, e = u.memoizedProps, u = u.memoizedState;
            var n = t.stateNode;
            try {
              var i = mu(
                t.type,
                e
              );
              a = n.getSnapshotBeforeUpdate(
                i,
                u
              ), n.__reactInternalSnapshotBeforeUpdate = a;
            } catch (c) {
              cl(t, t.return, c);
            }
          }
          break;
        case 3:
          if ((e & 1024) !== 0) {
            if (u = t.stateNode.containerInfo, a = u.nodeType, a === 9)
              is(u);
            else if (a === 1)
              switch (u.nodeName) {
                case "HEAD":
                case "HTML":
                case "BODY":
                  is(u);
                  break;
                default:
                  u.textContent = "";
              }
          }
          break;
        case 5:
        case 26:
        case 27:
        case 6:
        case 4:
        case 17:
          break;
        case 30:
          a && u !== null && (a = na(
            u.memoizedProps,
            u.stateNode
          ), e = t.memoizedProps, e = ia(e.default, e.update), e !== "none" && Lu(
            u,
            a,
            e,
            u.memoizedState = [],
            !0
          ));
          break;
        default:
          if ((e & 1024) !== 0) throw Error(r(163));
      }
      if (u = t.sibling, u !== null) {
        u.return = t.return, Hl = u;
        break;
      }
      Hl = t.return;
    }
  }
  function _v(l, t, a) {
    var u = a.flags;
    switch (a.tag) {
      case 0:
      case 11:
      case 15:
        kt(l, a), u & 4 && Xe(5, a);
        break;
      case 1:
        if (kt(l, a), u & 4)
          if (l = a.stateNode, t === null)
            try {
              l.componentDidMount();
            } catch (i) {
              cl(a, a.return, i);
            }
          else {
            var e = mu(
              a.type,
              t.memoizedProps
            );
            t = t.memoizedState;
            try {
              l.componentDidUpdate(
                e,
                t,
                l.__reactInternalSnapshotBeforeUpdate
              );
            } catch (i) {
              cl(
                a,
                a.return,
                i
              );
            }
          }
        u & 64 && dv(a), u & 512 && Jt(a, a.return);
        break;
      case 3:
        if (kt(l, a), u & 64 && (l = a.updateQueue, l !== null)) {
          if (t = null, a.child !== null)
            switch (a.child.tag) {
              case 27:
              case 5:
                t = a.child.stateNode;
                break;
              case 1:
                t = a.child.stateNode;
            }
          try {
            t0(l, t);
          } catch (i) {
            cl(a, a.return, i);
          }
        }
        break;
      case 27:
        t === null && u & 4 && yv(a);
      case 26:
      case 5:
        kt(l, a), t === null && u & 4 && gf(a), u & 512 && Jt(a, a.return);
        break;
      case 12:
        kt(l, a);
        break;
      case 31:
        kt(l, a), u & 4 && Mv(l, a);
        break;
      case 13:
        kt(l, a), u & 4 && jv(l, a), u & 64 && (l = a.memoizedState, l !== null && (l = l.dehydrated, l !== null && (a = tr.bind(
          null,
          a
        ), Yr(l, a))));
        break;
      case 22:
        if (u = a.memoizedState !== null || xl, !u) {
          var n = t !== null && t.memoizedState !== null || nl;
          t = xl, e = nl, xl = u, (nl = n) && !e ? (u = 2, (a.subtreeFlags & 8772) !== 0 && (u |= 1), pt(
            l,
            a,
            u
          )) : kt(l, a), xl = t, nl = e;
        }
        break;
      case 30:
        kt(l, a), u & 512 && Jt(a, a.return);
        break;
      case 7:
        u & 512 && Jt(a, a.return);
      default:
        kt(l, a);
    }
  }
  function Mf(l, t) {
    for (l = l.child; l !== null; )
      Ov(l, t), l = l.sibling;
  }
  function Ov(l, t) {
    switch (l.tag) {
      case 5:
      case 26:
        try {
          var a = l.stateNode;
          if (t) {
            var u = a.style;
            typeof u.setProperty == "function" ? u.setProperty("display", "none", "important") : u.display = "none";
          } else {
            var e = l.stateNode, n = l.memoizedProps.style, i = n != null && n.hasOwnProperty("display") ? n.display : null;
            e.style.display = i == null || typeof i == "boolean" ? "" : ("" + i).trim();
          }
        } catch (f) {
          cl(l, l.return, f);
        }
        jf(l, t);
        break;
      case 6:
        try {
          l.stateNode.nodeValue = t ? "" : l.memoizedProps, ll = !0;
        } catch (f) {
          cl(l, l.return, f);
        }
        break;
      case 18:
        try {
          var c = l.stateNode;
          t ? gd(c, !0) : gd(l.stateNode, !1);
        } catch (f) {
          cl(l, l.return, f);
        }
        break;
      case 22:
      case 23:
        l.memoizedState === null && Mf(l, t);
        break;
      default:
        Mf(l, t);
    }
  }
  function jf(l, t) {
    if (l.subtreeFlags & 67108864)
      for (l = l.child; l !== null; ) {
        l: {
          var a = l, u = t;
          switch (a.tag) {
            case 4:
              Ov(a, u);
              break l;
            case 22:
              a.memoizedState === null && jf(a, u);
              break l;
            default:
              jf(a, u);
          }
        }
        l = l.sibling;
      }
  }
  function Av(l) {
    var t = l.alternate;
    t !== null && (l.alternate = null, Av(t)), l.child = null, l.deletions = null, l.sibling = null, l.tag === 5 && (t = l.stateNode, t !== null && yn(t)), l.stateNode = null, l.return = null, l.dependencies = null, l.memoizedProps = null, l.memoizedState = null, l.pendingProps = null, l.stateNode = null, l.updateQueue = null;
  }
  var yl = null, lt = !1;
  function xt(l, t, a) {
    for (a = a.child; a !== null; )
      Dv(l, t, a), a = a.sibling;
  }
  function Dv(l, t, a) {
    if (st && typeof st.onCommitFiberUnmount == "function")
      try {
        st.onCommitFiberUnmount(ve, a);
      } catch {
      }
    switch (a.tag) {
      case 26:
        nl || Ql(a, t), xt(
          l,
          t,
          a
        ), a.memoizedState ? a.memoizedState.count-- : a.stateNode && !nl && (a = a.stateNode, a.parentNode.removeChild(a));
        break;
      case 27:
        nl || Ql(a, t), Qe(a);
        var u = yl, e = lt;
        Qa(a.type) && (yl = a.stateNode, lt = !1), xt(
          l,
          t,
          a
        ), Cd(
          a.stateNode,
          a.type,
          a.memoizedProps
        ), yl = u, lt = e;
        break;
      case 5:
        nl || Ql(a, t), Qe(a);
      case 6:
        if (a.tag === 6 && Qe(a), u = yl, e = lt, yl = null, xt(
          l,
          t,
          a
        ), yl = u, lt = e, yl !== null)
          if (lt)
            try {
              (yl.nodeType === 9 ? yl.body : yl.nodeName === "HTML" ? yl.ownerDocument.body : yl).removeChild(a.stateNode), ll = !0;
            } catch (n) {
              cl(
                a,
                t,
                n
              );
            }
          else
            try {
              yl.removeChild(a.stateNode), ll = !0;
            } catch (n) {
              cl(
                a,
                t,
                n
              );
            }
        break;
      case 18:
        yl !== null && (lt ? (l = yl, yd(
          l.nodeType === 9 ? l.body : l.nodeName === "HTML" ? l.ownerDocument.body : l,
          a.stateNode
        ), se(l)) : yd(yl, a.stateNode));
        break;
      case 4:
        u = yl, e = lt, yl = a.stateNode.containerInfo, lt = !0, xt(
          l,
          t,
          a
        ), yl = u, lt = e;
        break;
      case 0:
      case 11:
      case 14:
      case 15:
        Ha(2, a, t), nl || Ha(4, a, t), xt(
          l,
          t,
          a
        );
        break;
      case 1:
        nl || (Ql(a, t), u = a.stateNode, typeof u.componentWillUnmount == "function" && mv(
          a,
          t,
          u
        )), xt(
          l,
          t,
          a
        );
        break;
      case 21:
        xt(
          l,
          t,
          a
        );
        break;
      case 22:
        nl = (u = nl) || a.memoizedState !== null, xt(
          l,
          t,
          a
        ), nl = u;
        break;
      case 30:
        Ql(a, t), xt(
          l,
          t,
          a
        );
        break;
      case 7:
        nl || Ql(a, t), xt(
          l,
          t,
          a
        );
        break;
      default:
        xt(
          l,
          t,
          a
        );
    }
  }
  function Mv(l, t) {
    if (t.memoizedState === null && (l = t.alternate, l !== null && (l = l.memoizedState, l !== null))) {
      l = l.dehydrated;
      try {
        se(l);
      } catch (a) {
        cl(t, t.return, a);
      }
    }
  }
  function jv(l, t) {
    if (t.memoizedState === null && (l = t.alternate, l !== null && (l = l.memoizedState, l !== null && (l = l.dehydrated, l !== null))))
      try {
        se(l);
      } catch (a) {
        cl(t, t.return, a);
      }
  }
  function Lh(l) {
    switch (l.tag) {
      case 31:
      case 13:
      case 19:
        var t = l.stateNode;
        return t === null && (t = l.stateNode = new Nv()), t;
      case 22:
        return l = l.stateNode, t = l._retryCache, t === null && (t = l._retryCache = new Nv()), t;
      default:
        throw Error(r(435, l.tag));
    }
  }
  function di(l, t) {
    var a = Lh(l);
    t.forEach(function(u) {
      if (!a.has(u)) {
        a.add(u);
        var e = ar.bind(null, l, u);
        u.then(e, e);
      }
    });
  }
  function wl(l, t, a) {
    var u = t.deletions;
    if (u !== null)
      for (var e = 0; e < u.length; e++) {
        var n = u[e], i = l, c = t, f = c;
        l: for (; f !== null; ) {
          switch (f.tag) {
            case 27:
              if (Qa(f.type)) {
                yl = f.stateNode, lt = !1;
                break l;
              }
              break;
            case 5:
              yl = f.stateNode, lt = !1;
              break l;
            case 3:
            case 4:
              yl = f.stateNode.containerInfo, lt = !0;
              break l;
          }
          f = f.return;
        }
        if (yl === null) throw Error(r(160));
        Dv(i, c, n), yl = null, lt = !1, i = n.alternate, i !== null && (i.return = null), n.return = null;
      }
    if (t.subtreeFlags & 13886)
      for (t = t.child; t !== null; )
        Uv(t, l, a), t = t.sibling;
  }
  var Ht = null;
  function Uv(l, t, a) {
    var u = l.alternate, e = l.flags;
    switch (l.tag) {
      case 0:
      case 11:
      case 14:
      case 15:
        if (e & 4 && (u = l.updateQueue, u = u !== null ? u.events : null, u !== null))
          for (var n = 0; n < u.length; n++) {
            var i = u[n];
            i.ref.impl = i.nextImpl;
          }
        wl(t, l, a), Fl(l), e & 4 && (Ha(3, l, l.return), Xe(3, l), Ha(5, l, l.return));
        break;
      case 1:
        wl(t, l, a), Fl(l), e & 512 && (nl || u === null || Ql(u, u.return)), e & 64 && xl && (l = l.updateQueue, l !== null && (t = l.callbacks, t !== null && (a = l.shared.hiddenCallbacks, l.shared.hiddenCallbacks = a === null ? t : a.concat(t))));
        break;
      case 26:
        if (n = Ht, wl(t, l, a), Fl(l), e & 512 && (nl || u === null || Ql(u, u.return)), e & 4)
          if (e = u !== null ? u.memoizedState : null, a = l.memoizedState, u === null)
            if (a === null)
              if (l.stateNode === null)
                if (xl)
                  l.stateNode = md(
                    l.type,
                    l.memoizedProps,
                    t.containerInfo,
                    l
                  );
                else {
                  l: {
                    t = l.type, a = l.memoizedProps, e = n.ownerDocument || n;
                    t: switch (t) {
                      case "title":
                        u = e.getElementsByTagName("title")[0], (!u || u[he] || u[ql] || u.namespaceURI === "http://www.w3.org/2000/svg" || u.hasAttribute("itemprop")) && (u = e.createElement(t), e.head.insertBefore(
                          u,
                          e.querySelector("head > title")
                        )), Vl(u, t, a), u[ql] = l, Cl(u), t = u;
                        break l;
                      case "link":
                        if (n = qd(
                          "link",
                          "href",
                          e
                        ).get(t + (a.href || ""))) {
                          for (i = 0; i < n.length; i++)
                            if (u = n[i], u.getAttribute("href") === (a.href == null || a.href === "" ? null : a.href) && u.getAttribute("rel") === (a.rel == null ? null : a.rel) && u.getAttribute("title") === (a.title == null ? null : a.title) && u.getAttribute("crossorigin") === (a.crossOrigin == null ? null : a.crossOrigin)) {
                              n.splice(i, 1);
                              break t;
                            }
                        }
                        u = e.createElement(t), Vl(u, t, a), e.head.appendChild(u);
                        break;
                      case "meta":
                        if (n = qd(
                          "meta",
                          "content",
                          e
                        ).get(t + (a.content || ""))) {
                          for (i = 0; i < n.length; i++)
                            if (u = n[i], u.getAttribute("content") === (a.content == null ? null : "" + a.content) && u.getAttribute("name") === (a.name == null ? null : a.name) && u.getAttribute("property") === (a.property == null ? null : a.property) && u.getAttribute("http-equiv") === (a.httpEquiv == null ? null : a.httpEquiv) && u.getAttribute("charset") === (a.charSet == null ? null : a.charSet)) {
                              n.splice(i, 1);
                              break t;
                            }
                        }
                        u = e.createElement(t), Vl(u, t, a), e.head.appendChild(u);
                        break;
                      default:
                        throw Error(r(468, t));
                    }
                    u[ql] = l, Cl(u), t = u;
                  }
                  l.stateNode = t;
                }
              else
                xl || ms(n, l.type, l.stateNode);
            else
              l.stateNode = Yd(
                n,
                a,
                l.memoizedProps
              );
          else
            e !== a ? (e === null ? (t = u.stateNode, t === null || nl || t.parentNode.removeChild(t)) : e.count--, a === null ? xl || ms(n, l.type, l.stateNode) : Yd(n, a, l.memoizedProps)) : a === null && l.stateNode !== null && Sf(
              l,
              l.memoizedProps,
              u.memoizedProps
            );
        break;
      case 27:
        wl(t, l, a), Fl(l), e & 512 && (nl || u === null || Ql(u, u.return)), u !== null && e & 4 && Sf(
          l,
          l.memoizedProps,
          u.memoizedProps
        );
        break;
      case 5:
        if (n = $t, $t = !1, wl(t, l, a), $t = n, Fl(l), e & 512 && (nl || u === null || Ql(u, u.return)), l.flags & 32) {
          t = l.stateNode;
          try {
            Au(t, ""), ll = !0;
          } catch (S) {
            cl(l, l.return, S);
          }
        }
        e & 4 && l.stateNode != null && (t = l.memoizedProps, Sf(
          l,
          t,
          u !== null ? u.memoizedProps : t
        )), e & 1024 && (Af = !0);
        break;
      case 6:
        if (wl(t, l, a), Fl(l), e & 4) {
          if (l.stateNode === null)
            throw Error(r(162));
          t = l.memoizedProps, a = l.stateNode;
          try {
            a.nodeValue = t, ll = !0;
          } catch (S) {
            cl(l, l.return, S);
          }
        }
        break;
      case 3:
        if (ll = !1, Di = null, n = Ht, Ht = ke(t.containerInfo), wl(t, l, a), Ht = n, Fl(l), e & 4 && u !== null && u.memoizedState.isDehydrated)
          try {
            se(t.containerInfo);
          } catch (S) {
            cl(l, l.return, S);
          }
        Af && (Af = !1, Cv(l)), ll = !1;
        break;
      case 4:
        e = $t, $t = xl, u = Ws(), n = Ht, Ht = ke(
          l.stateNode.containerInfo
        ), wl(t, l, a), Fl(l), Ht = n, ll && Ve && (oi = !0), ll = u, $t = e;
        break;
      case 12:
        wl(t, l, a), Fl(l);
        break;
      case 31:
        wl(t, l, a), Fl(l), e & 4 && (t = l.updateQueue, t !== null && (l.updateQueue = null, di(l, t)));
        break;
      case 13:
        wl(t, l, a), Fl(l), l.child.flags & 8192 && l.memoizedState !== null != (u !== null && u.memoizedState !== null) && (ri = ft()), e & 4 && (t = l.updateQueue, t !== null && (l.updateQueue = null, di(l, t)));
        break;
      case 22:
        n = l.memoizedState !== null, i = u !== null && u.memoizedState !== null;
        var c = xl, f = nl, h = $t;
        xl = c || n, $t = h || n, nl = f || i, wl(t, l, a), nl = f, $t = h, xl = c, Fl(l), e & 8192 && (t = l.stateNode, t._visibility = n ? t._visibility & -2 : t._visibility | 1, !n || u === null || i || xl || nl || (t = i || nl, a = xl, u = nl, xl = n || xl, nl = t, pa(l, 2), xl = a, nl = u), !n && $t || Mf(l, n)), e & 4 && (t = l.updateQueue, t !== null && (a = t.retryQueue, a !== null && (t.retryQueue = null, di(l, a))));
        break;
      case 19:
        wl(t, l, a), Fl(l), e & 4 && (t = l.updateQueue, t !== null && (l.updateQueue = null, di(l, t)));
        break;
      case 30:
        e & 512 && (nl || u === null || Ql(u, u.return)), e = Ws(), n = Ve, i = (a & 335544064) === a, c = l.memoizedProps, Ve = i && ia(
          c.default,
          c.update
        ) !== "none", wl(t, l, a), Fl(l), i && u !== null && ll && (l.flags |= 4), Ve = n, ll = e;
        break;
      case 21:
        break;
      case 7:
        e & 512 && (nl || u === null || Ql(u, u.return)), u && u.stateNode !== null && (u.stateNode._fragmentFiber = l);
      default:
        wl(t, l, a), Fl(l);
    }
  }
  function Fl(l) {
    var t = l.flags;
    if (t & 2) {
      try {
        for (var a, u = l.return; u !== null; ) {
          if (rv(u)) {
            a = u;
            break;
          }
          u = u.return;
        }
        u = null;
        for (var e = l.return; e !== null; ) {
          if (yf(e)) {
            var n = e.stateNode;
            u === null ? u = [n] : u.push(n);
          }
          if (rf(e)) break;
          e = e.return;
        }
        var i = u;
        if (a == null) throw Error(r(160));
        switch (a.tag) {
          case 27:
            var c = a.stateNode, f = Tf(l);
            ii(
              l,
              f,
              c,
              i
            );
            break;
          case 5:
            var h = a.stateNode;
            a.flags & 32 && (Au(h, ""), a.flags &= -33);
            var S = Tf(l);
            ii(
              l,
              S,
              h,
              i
            );
            break;
          case 3:
          case 4:
            var b = a.stateNode.containerInfo, d = Tf(l);
            Ef(
              l,
              d,
              b,
              i
            );
            break;
          default:
            throw Error(r(161));
        }
      } catch (g) {
        cl(l, l.return, g);
      }
      l.flags &= -3;
    }
    t & 4096 && (l.flags &= -4097);
  }
  function Cv(l) {
    if (l.subtreeFlags & 1024)
      for (l = l.child; l !== null; ) {
        var t = l;
        Cv(t), t.tag === 5 && t.flags & 1024 && (t = t.stateNode, fe = !0, t.reset(), fe = !1), l = l.sibling;
      }
  }
  function Ku(l, t) {
    if (t.subtreeFlags & 9270)
      for (t = t.child; t !== null; )
        Rv(t, l), t = t.sibling;
    else zv(t);
  }
  function Rv(l, t) {
    var a = l.alternate;
    if (a === null) bf(l, !1);
    else
      switch (l.tag) {
        case 3:
          if (Df = Wt = !1, Sv(), Ku(t, l), !Wt && !oi) {
            if (l = wt, l !== null)
              for (var u = 0; u < l.length; u += 3) {
                a = l[u];
                var e = l[u + 1];
                Td(a, l[u + 2]), a = a.ownerDocument.documentElement, a !== null && a.animate(
                  { opacity: [0, 0], pointerEvents: ["none", "none"] },
                  {
                    duration: 0,
                    fill: "forwards",
                    pseudoElement: "::view-transition-group(" + e + ")"
                  }
                );
              }
            l = t.containerInfo, l = l.nodeType === 9 ? l.documentElement : l.ownerDocument.documentElement, l !== null && l.style.viewTransitionName === "" && (l.style.viewTransitionName = "none", l.animate(
              { opacity: [0, 0], pointerEvents: ["none", "none"] },
              {
                duration: 0,
                fill: "forwards",
                pseudoElement: "::view-transition-group(root)"
              }
            ), l.animate(
              { width: [0, 0], height: [0, 0] },
              {
                duration: 0,
                fill: "forwards",
                pseudoElement: "::view-transition"
              }
            )), Df = !0;
          }
          wt = null;
          break;
        case 5:
          Ku(t, l);
          break;
        case 4:
          u = Wt, Wt = !1, Ku(t, l), Wt && (oi = !0), Wt = u;
          break;
        case 22:
          l.memoizedState === null && (a.memoizedState !== null ? bf(l, !1) : Ku(t, l));
          break;
        case 30:
          u = Wt, e = Sv(), Wt = !1, Ku(t, l), Wt && (l.flags |= 4);
          var n = l.memoizedProps, i = l.stateNode;
          t = na(n, i), i = na(a.memoizedProps, i);
          var c = ia(n.default, n.update);
          c === "none" ? t = !1 : (n = a.memoizedState, a.memoizedState = null, a = l.child, Pl = 0, t = Of(
            l,
            a,
            t,
            i,
            c,
            n,
            !0
          ), Pl !== (n === null ? 0 : n.length) && (l.flags |= 32)), (l.flags & 4) !== 0 && t ? (Iu(
            l,
            l.memoizedProps.onUpdate
          ), wt = e) : e !== null && (e.push.apply(e, wt), wt = e), Wt = (l.flags & 32) !== 0 ? !0 : u;
          break;
        default:
          Ku(t, l);
      }
  }
  function kt(l, t) {
    if (t.subtreeFlags & 8772)
      for (t = t.child; t !== null; )
        _v(l, t.alternate, t), t = t.sibling;
  }
  function pa(l, t) {
    for (l = l.child; l !== null; ) {
      var a = l, u = t;
      switch (a.tag) {
        case 0:
        case 11:
        case 14:
        case 15:
          Ha(4, a, a.return), pa(
            a,
            u
          );
          break;
        case 1:
          Ql(a, a.return);
          var e = a.stateNode;
          typeof e.componentWillUnmount == "function" && mv(
            a,
            a.return,
            e
          ), pa(
            a,
            u
          );
          break;
        case 27:
          (u & 2) !== 0 && Cd(
            a.stateNode,
            a.type,
            a.memoizedProps
          );
        case 5:
          Ql(a, a.return), a.tag !== 5 && a.tag !== 27 || Qe(a), pa(
            a,
            u
          );
          break;
        case 6:
          Qe(a);
          break;
        case 26:
          Ql(a, a.return), e = a.stateNode, a.memoizedState !== null || e === null || nl || e.parentNode.removeChild(e), pa(
            a,
            u
          );
          break;
        case 22:
          a.memoizedState === null && pa(
            a,
            u
          );
          break;
        case 30:
          Ql(a, a.return), pa(
            a,
            u
          );
          break;
        case 7:
          Ql(a, a.return);
        default:
          pa(
            a,
            u
          );
      }
      l = l.sibling;
    }
  }
  function pt(l, t, a) {
    for (a = (t.subtreeFlags & 8772) !== 0 ? a : a & -2, t = t.child; t !== null; ) {
      var u = t.alternate, e = l, n = t, i = n.flags, c = (a & 1) !== 0;
      switch (n.tag) {
        case 0:
        case 11:
        case 15:
          pt(
            e,
            n,
            a
          ), Xe(4, n);
          break;
        case 1:
          if (pt(
            e,
            n,
            a
          ), u = n, e = u.stateNode, typeof e.componentDidMount == "function")
            try {
              e.componentDidMount();
            } catch (S) {
              cl(u, u.return, S);
            }
          if (u = n, e = u.updateQueue, e !== null) {
            var f = u.stateNode;
            try {
              var h = e.shared.hiddenCallbacks;
              if (h !== null)
                for (e.shared.hiddenCallbacks = null, e = 0; e < h.length; e++)
                  l0(h[e], f);
            } catch (S) {
              cl(u, u.return, S);
            }
          }
          c && i & 64 && dv(n), Jt(n, n.return);
          break;
        case 27:
          (a & 2) !== 0 && yv(n);
        case 5:
          n.tag !== 5 && n.tag !== 27 || hv(n), pt(
            e,
            n,
            a
          ), c && u === null && i & 4 && gf(n), Jt(n, n.return);
          break;
        case 6:
          hv(n);
          break;
        case 26:
          f = n.stateNode, n.memoizedState !== null || f === null || xl || ms(
            ke(f.ownerDocument),
            n.type,
            f
          ), pt(
            e,
            n,
            a
          ), c && u === null && i & 4 && gf(n), Jt(n, n.return);
          break;
        case 12:
          pt(
            e,
            n,
            a
          );
          break;
        case 31:
          pt(
            e,
            n,
            a
          ), c && i & 4 && Mv(e, n);
          break;
        case 13:
          pt(
            e,
            n,
            a
          ), c && i & 4 && jv(e, n);
          break;
        case 22:
          n.memoizedState === null && pt(
            e,
            n,
            a
          ), Jt(n, n.return);
          break;
        case 30:
          pt(
            e,
            n,
            a
          ), Jt(n, n.return);
          break;
        case 7:
          Jt(n, n.return);
        default:
          pt(
            e,
            n,
            a
          );
      }
      t = t.sibling;
    }
  }
  function Uf(l, t) {
    var a = null;
    l !== null && l.memoizedState !== null && l.memoizedState.cachePool !== null && (a = l.memoizedState.cachePool.pool), l = null, t.memoizedState !== null && t.memoizedState.cachePool !== null && (l = t.memoizedState.cachePool.pool), l !== a && (l != null && l.refCount++, a != null && De(a));
  }
  function Cf(l, t) {
    l = null, t.alternate !== null && (l = t.alternate.memoizedState.cache), t = t.memoizedState.cache, t !== l && (t.refCount++, l != null && De(l));
  }
  function Mt(l, t, a, u) {
    var e = (a & 335544064) === a;
    if (t.subtreeFlags & (e ? 10262 : 10256))
      for (t = t.child; t !== null; )
        xv(
          l,
          t,
          a,
          u
        ), t = t.sibling;
    else e && bv(t);
  }
  function xv(l, t, a, u) {
    var e = (a & 335544064) === a;
    e && t.alternate === null && t.return !== null && t.return.alternate !== null && si(t);
    var n = t.flags;
    switch (t.tag) {
      case 0:
      case 11:
      case 15:
        Mt(
          l,
          t,
          a,
          u
        ), n & 2048 && Xe(9, t);
        break;
      case 1:
        Mt(
          l,
          t,
          a,
          u
        );
        break;
      case 3:
        Mt(
          l,
          t,
          a,
          u
        ), e && Df && (l = l.containerInfo, l = l.nodeType === 9 ? l.body : l.nodeName === "HTML" ? l.ownerDocument.body : l, l.style.viewTransitionName === "root" && (l.style.viewTransitionName = ""), l = l.ownerDocument.documentElement, l !== null && l.style.viewTransitionName === "none" && (l.style.viewTransitionName = "")), n & 2048 && (n = null, t.alternate !== null && (n = t.alternate.memoizedState.cache), t = t.memoizedState.cache, t !== n && (t.refCount++, n != null && De(n)));
        break;
      case 12:
        if (n & 2048) {
          Mt(
            l,
            t,
            a,
            u
          ), n = t.stateNode;
          try {
            var i = t.memoizedProps, c = i.id, f = i.onPostCommit;
            typeof f == "function" && f(
              c,
              t.alternate === null ? "mount" : "update",
              n.passiveEffectDuration,
              -0
            );
          } catch (h) {
            cl(t, t.return, h);
          }
        } else
          Mt(
            l,
            t,
            a,
            u
          );
        break;
      case 31:
        Mt(
          l,
          t,
          a,
          u
        );
        break;
      case 13:
        Mt(
          l,
          t,
          a,
          u
        );
        break;
      case 23:
        break;
      case 22:
        i = t.stateNode, c = t.alternate, t.memoizedState !== null ? (e && c !== null && c.memoizedState === null && si(c), i._visibility & 2 ? Mt(
          l,
          t,
          a,
          u
        ) : Ze(
          l,
          t
        )) : (e && c !== null && c.memoizedState !== null && si(t), i._visibility & 2 ? Mt(
          l,
          t,
          a,
          u
        ) : (i._visibility |= 2, Ju(
          l,
          t,
          a,
          u,
          (t.subtreeFlags & 10256) !== 0 || !1
        ))), n & 2048 && Uf(c, t);
        break;
      case 24:
        Mt(
          l,
          t,
          a,
          u
        ), n & 2048 && Cf(t.alternate, t);
        break;
      case 30:
        e && (n = t.alternate, n !== null && (Ft(n.child, !0), Ft(t.child, !0))), Mt(
          l,
          t,
          a,
          u
        );
        break;
      default:
        Mt(
          l,
          t,
          a,
          u
        );
    }
  }
  function Ju(l, t, a, u, e) {
    for (e = e && ((t.subtreeFlags & 10256) !== 0 || !1), t = t.child; t !== null; ) {
      var n = l, i = t, c = a, f = u, h = i.flags;
      switch (i.tag) {
        case 0:
        case 11:
        case 15:
          Ju(
            n,
            i,
            c,
            f,
            e
          ), Xe(8, i);
          break;
        case 23:
          break;
        case 22:
          var S = i.stateNode;
          i.memoizedState !== null ? S._visibility & 2 ? Ju(
            n,
            i,
            c,
            f,
            e
          ) : Ze(
            n,
            i
          ) : (S._visibility |= 2, Ju(
            n,
            i,
            c,
            f,
            e
          )), e && h & 2048 && Uf(
            i.alternate,
            i
          );
          break;
        case 24:
          Ju(
            n,
            i,
            c,
            f,
            e
          ), e && h & 2048 && Cf(i.alternate, i);
          break;
        default:
          Ju(
            n,
            i,
            c,
            f,
            e
          );
      }
      t = t.sibling;
    }
  }
  function Ze(l, t) {
    if (t.subtreeFlags & 10256)
      for (t = t.child; t !== null; ) {
        var a = l, u = t, e = u.flags;
        switch (u.tag) {
          case 22:
            Ze(a, u), e & 2048 && Uf(
              u.alternate,
              u
            );
            break;
          case 24:
            Ze(a, u), e & 2048 && Cf(u.alternate, u);
            break;
          default:
            Ze(a, u);
        }
        t = t.sibling;
      }
  }
  var hu = 8192;
  function ru(l, t, a) {
    if (l.subtreeFlags & hu)
      for (l = l.child; l !== null; )
        Hv(
          l,
          t,
          a
        ), l = l.sibling;
  }
  function Hv(l, t, a) {
    switch (l.tag) {
      case 26:
        ru(
          l,
          t,
          a
        ), l.flags & hu && (l.memoizedState !== null ? Wr(
          a,
          Ht,
          l.memoizedState,
          l.memoizedProps
        ) : (l = l.stateNode, (t & 335544128) === t && Qd(a, l)));
        break;
      case 5:
        ru(
          l,
          t,
          a
        ), l.flags & hu && (l = l.stateNode, (t & 335544128) === t && Qd(a, l));
        break;
      case 3:
      case 4:
        var u = Ht;
        Ht = ke(l.stateNode.containerInfo), ru(
          l,
          t,
          a
        ), Ht = u;
        break;
      case 22:
        l.memoizedState === null && (u = l.alternate, u !== null && u.memoizedState !== null ? (u = hu, hu = 16777216, ru(
          l,
          t,
          a
        ), hu = u) : ru(
          l,
          t,
          a
        ));
        break;
      case 30:
        if ((l.flags & hu) !== 0 && (u = l.memoizedProps.name, u != null && u !== "auto")) {
          var e = l.stateNode;
          e.paired = null, ht === null && (ht = /* @__PURE__ */ new Map()), ht.set(u, e);
        }
        ru(
          l,
          t,
          a
        );
        break;
      default:
        ru(
          l,
          t,
          a
        );
    }
  }
  function pv(l) {
    var t = l.alternate;
    if (t !== null && (l = t.child, l !== null)) {
      t.child = null;
      do
        t = l.sibling, l.sibling = null, l = t;
      while (l !== null);
    }
  }
  function Le(l) {
    var t = l.deletions;
    if ((l.flags & 16) !== 0) {
      if (t !== null)
        for (var a = 0; a < t.length; a++) {
          var u = t[a];
          Hl = u, qv(
            u,
            l
          );
        }
      pv(l);
    }
    if (l.subtreeFlags & 10256)
      for (l = l.child; l !== null; )
        Yv(l), l = l.sibling;
  }
  function Yv(l) {
    switch (l.tag) {
      case 0:
      case 11:
      case 15:
        Le(l), l.flags & 2048 && Ha(9, l, l.return);
        break;
      case 3:
        Le(l);
        break;
      case 12:
        Le(l);
        break;
      case 22:
        var t = l.stateNode;
        l.memoizedState !== null && t._visibility & 2 && (l.return === null || l.return.tag !== 13) ? (t._visibility &= -3, mi(l)) : Le(l);
        break;
      default:
        Le(l);
    }
  }
  function mi(l) {
    var t = l.deletions;
    if ((l.flags & 16) !== 0) {
      if (t !== null)
        for (var a = 0; a < t.length; a++) {
          var u = t[a];
          Hl = u, qv(
            u,
            l
          );
        }
      pv(l);
    }
    for (l = l.child; l !== null; ) {
      switch (t = l, t.tag) {
        case 0:
        case 11:
        case 15:
          Ha(8, t, t.return), mi(t);
          break;
        case 22:
          a = t.stateNode, a._visibility & 2 && (a._visibility &= -3, mi(t));
          break;
        default:
          mi(t);
      }
      l = l.sibling;
    }
  }
  function qv(l, t) {
    for (; Hl !== null; ) {
      var a = Hl;
      switch (a.tag) {
        case 0:
        case 11:
        case 15:
          Ha(8, a, t);
          break;
        case 23:
        case 22:
          if (a.memoizedState !== null && a.memoizedState.cachePool !== null) {
            var u = a.memoizedState.cachePool.pool;
            u != null && u.refCount++;
          }
          break;
        case 24:
          De(a.memoizedState.cache);
      }
      if (u = a.child, u !== null) u.return = a, Hl = u;
      else
        l: for (a = l; Hl !== null; ) {
          u = Hl;
          var e = u.sibling, n = u.return;
          if (Av(u), u === a) {
            Hl = null;
            break l;
          }
          if (e !== null) {
            e.return = n, Hl = e;
            break l;
          }
          Hl = n;
        }
    }
  }
  var Kh = {
    getCacheForType: function(l) {
      var t = Bl(zl), a = t.data.get(l);
      return a === void 0 && (a = l(), t.data.set(l, a)), a;
    },
    cacheSignal: function() {
      return Bl(zl).controller.signal;
    }
  }, Jh = typeof WeakMap == "function" ? WeakMap : Map, ul = 0, vl = null, J = null, F = 0, il = 0, rt = null, Ya = !1, wu = !1, Rf = !1, ra = 0, Tl = 0, qa = 0, yu = 0, hi = 0, yt = 0, Fu = 0, Ke = null, tt = null, xf = !1, ri = 0, Bv = 0, yi = 1 / 0, gi = null, Ba = null, gl = 0, Yt = null, gu = null, It = 0, Hf = 0, pf = null, Gv = null, $u = null, Wu = null, ku = null, Je = 0, Si = null;
  function gt() {
    return (ul & 2) !== 0 && F !== 0 ? F & -F : M.T !== null ? Kf() : Qs();
  }
  function Xv() {
    if (yt === 0)
      if ((F & 536870912) === 0 || L) {
        var l = dn;
        dn <<= 1, (dn & 3932160) === 0 && (dn = 262144), yt = l;
      } else yt = 536870912;
    return l = Gl.current, l !== null && (l.flags |= 32), yt;
  }
  function Iu(l, t) {
    if (t != null) {
      var a = l.stateNode, u = a.ref;
      u === null && (u = a.ref = Ed(
        na(l.memoizedProps, a)
      )), Wu === null && (Wu = []), Wu.push(t.bind(null, u));
    }
  }
  function at(l, t, a) {
    (l === vl && (il === 2 || il === 9) || l.cancelPendingCommit !== null) && (Pu(l, 0), Ga(
      l,
      F,
      yt,
      !1
    )), me(l, a), ((ul & 2) === 0 || l !== vl) && (l === vl && ((ul & 2) === 0 && (yu |= a), Tl === 4 && Ga(
      l,
      F,
      yt,
      !1
    )), Pt(l));
  }
  function Qv(l, t, a) {
    if ((ul & 6) !== 0) throw Error(r(327));
    var u = !a && (t & 127) === 0 && (t & l.expiredLanes) === 0 || de(l, t), e = u ? $h(l, t) : qf(l, t, !0), n = u;
    do {
      if (e === 0) {
        wu && !u && Ga(l, t, 0, !1);
        break;
      } else {
        if (a = l.current.alternate, n && !wh(a)) {
          e = qf(l, t, !1), n = !1;
          continue;
        }
        if (e === 2) {
          if (n = t, l.errorRecoveryDisabledLanes & n)
            var i = 0;
          else
            i = l.pendingLanes & -536870913, i = i !== 0 ? i : i & 536870912 ? 536870912 : 0;
          if (i !== 0) {
            t = i;
            l: {
              var c = l;
              e = Ke;
              var f = c.current.memoizedState.isDehydrated;
              if (f && (Pu(c, i).flags |= 256), i = qf(
                c,
                i,
                !1
              ), i !== 2 && i !== 6) {
                if (Rf && !f) {
                  c.errorRecoveryDisabledLanes |= n, yu |= n, e = 4;
                  break l;
                }
                n = tt, tt = e, n !== null && (tt === null ? tt = n : tt.push.apply(
                  tt,
                  n
                ));
              }
              e = i;
            }
            if (n = !1, e !== 2) continue;
          }
        }
        if (e === 1) {
          Pu(l, 0), Ga(l, t, 0, !0);
          break;
        }
        l: {
          switch (u = l, n = e, n) {
            case 0:
            case 1:
              throw Error(r(345));
            case 4:
              if ((t & 4194048) !== t && (t & 62914560) !== t)
                break;
            case 6:
              Ga(
                u,
                t,
                yt,
                !Ya
              );
              break l;
            case 2:
              tt = null;
              break;
            case 3:
            case 5:
              break;
            default:
              throw Error(r(329));
          }
          if ((t & 62914560) === t && (e = ri + 300 - ft(), 10 < e)) {
            if (Ga(
              u,
              t,
              yt,
              !Ya
            ), hn(u, 0, !0) !== 0) break l;
            It = t, u.timeoutHandle = as(
              Vv.bind(
                null,
                u,
                a,
                tt,
                gi,
                xf,
                t,
                yt,
                yu,
                Fu,
                Ya,
                n,
                "Throttled",
                -0,
                0
              ),
              e
            );
            break l;
          }
          Vv(
            u,
            a,
            tt,
            gi,
            xf,
            t,
            yt,
            yu,
            Fu,
            Ya,
            n,
            null,
            -0,
            0
          );
        }
      }
      break;
    } while (!0);
    Pt(l);
  }
  function Vv(l, t, a, u, e, n, i, c, f, h, S, b, d, g) {
    l.timeoutHandle = -1;
    var _ = t.subtreeFlags, D = (n & 335544064) === n;
    if (b = null, (D || _ & 8192 || (_ & 16785408) === 16785408) && (b = {
      stylesheets: null,
      count: 0,
      imgCount: 0,
      imgBytes: 0,
      suspenseyImages: [],
      waitingForImages: !0,
      waitingForViewTransition: !1,
      unsuspend: Zt
    }, ht = null, Hv(
      t,
      n,
      b
    ), D && (_ = b, D = l.containerInfo, D = (D.nodeType === 9 ? D : D.ownerDocument).__reactViewTransition, D != null && (_.count++, _.waitingForViewTransition = !0, _ = ln.bind(_), D.finished.then(_, _))), _ = (n & 62914560) === n ? ri - ft() : (n & 4194048) === n ? Bv - ft() : 0, _ = kr(
      b,
      _
    ), _ !== null)) {
      It = n, l.cancelPendingCommit = _(
        Wv.bind(
          null,
          l,
          t,
          n,
          a,
          u,
          e,
          i,
          c,
          f,
          h,
          S,
          b,
          null,
          d,
          g
        )
      ), Ga(l, n, i, !h);
      return;
    }
    Wv(
      l,
      t,
      n,
      a,
      u,
      e,
      i,
      c,
      f,
      h,
      S,
      b
    );
  }
  function wh(l) {
    for (var t = l; ; ) {
      var a = t.tag;
      if ((a === 0 || a === 11 || a === 15) && t.flags & 16384 && (a = t.updateQueue, a !== null && (a = a.stores, a !== null)))
        for (var u = 0; u < a.length; u++) {
          var e = a[u], n = e.getSnapshot;
          e = e.value;
          try {
            if (!dt(n(), e)) return !1;
          } catch {
            return !1;
          }
        }
      if (a = t.child, t.subtreeFlags & 16384 && a !== null)
        a.return = t, t = a;
      else {
        if (t === l) break;
        for (; t.sibling === null; ) {
          if (t.return === null || t.return === l) return !0;
          t = t.return;
        }
        t.sibling.return = t.return, t = t.sibling;
      }
    }
    return !0;
  }
  function Ga(l, t, a, u) {
    t = Ys(l, t), t &= ~hi, t &= ~yu, l.suspendedLanes |= t, l.pingedLanes &= ~t, u && (l.warmLanes |= t), u = l.expirationTimes;
    for (var e = t; 0 < e; ) {
      var n = 31 - ot(e), i = 1 << n;
      u[n] = -1, e &= ~i;
    }
    a !== 0 && Bs(l, a, t);
  }
  function Ti() {
    return (ul & 6) === 0 ? (we(0), !1) : !0;
  }
  function Yf() {
    if (J !== null) {
      if (il === 0)
        var l = J.return;
      else
        l = J, sa = eu = null, Zc(l), Gu = null, Ue = 0, l = J;
      for (; l !== null; )
        vv(l.alternate, l), l = l.return;
      J = null;
    }
  }
  function Pu(l, t) {
    var a = l.timeoutHandle;
    return a !== -1 && (l.timeoutHandle = -1, gr(a)), a = l.cancelPendingCommit, a !== null && (l.cancelPendingCommit = null, a()), It = 0, Yf(), vl = l, J = a = ca(l.current, null), F = t, il = 0, rt = null, Ya = !1, wu = de(l, t), Rf = !1, Fu = yt = hi = yu = qa = Tl = 0, tt = Ke = null, xf = !1, ra = Ys(l, t), Dn(), a;
  }
  function Zv(l, t) {
    V = null, M.H = In, t === Bu || t === Bn ? (t = Wo(), il = 3) : t === Uc ? (t = Wo(), il = 4) : il = t === ef ? 8 : t !== null && typeof t == "object" && typeof t.then == "function" ? 6 : 1, rt = t, J === null && (Tl = 1, Pn(
      l,
      _t(t, l.current)
    ));
  }
  function Lv() {
    var l = Gl.current;
    return l === null ? !0 : (F & 4194048) === F ? Ll === null : (F & 62914560) === F || (F & 536870912) !== 0 ? l === Ll : !1;
  }
  function Kv() {
    var l = M.H;
    return M.H = In, l === null ? In : l;
  }
  function Jv() {
    var l = M.A;
    return M.A = Kh, l;
  }
  function Ei() {
    Tl = 4, Ya || (F & 4194048) !== F && Gl.current !== null || (wu = !0), (qa & 134217727) === 0 && (yu & 134217727) === 0 || vl === null || Ga(
      vl,
      F,
      yt,
      !1
    );
  }
  function qf(l, t, a) {
    var u = ul;
    ul |= 2;
    var e = Kv(), n = Jv();
    (vl !== l || F !== t) && (gi = null, Pu(l, t)), t = !1;
    var i = Tl;
    l: do
      try {
        if (il !== 0 && J !== null) {
          var c = J, f = rt;
          switch (il) {
            case 8:
              Yf(), i = 6;
              break l;
            case 3:
            case 2:
            case 9:
            case 6:
              Gl.current === null && (t = !0);
              var h = il;
              if (il = 0, rt = null, le(l, c, f, h), a && wu) {
                i = 0;
                break l;
              }
              break;
            default:
              h = il, il = 0, rt = null, le(l, c, f, h);
          }
        }
        Fh(), i = Tl;
        break;
      } catch (S) {
        Zv(l, S);
      }
    while (!0);
    return t && l.shellSuspendCounter++, sa = eu = null, ul = u, M.H = e, M.A = n, J === null && (vl = null, F = 0, Dn()), i;
  }
  function Fh() {
    for (; J !== null; ) wv(J);
  }
  function $h(l, t) {
    var a = ul;
    ul |= 2;
    var u = Kv(), e = Jv();
    vl !== l || F !== t ? (gi = null, yi = ft() + 500, Pu(l, t)) : wu = de(
      l,
      t
    );
    l: do
      try {
        if (il !== 0 && J !== null) {
          t = J;
          var n = rt;
          t: switch (il) {
            case 1:
              il = 0, rt = null, le(l, t, n, 1);
              break;
            case 2:
            case 9:
              if (Fo(n)) {
                il = 0, rt = null, Fv(t);
                break;
              }
              t = function() {
                il !== 2 && il !== 9 || vl !== l || (il = 7), Pt(l);
              }, n.then(t, t);
              break l;
            case 3:
              il = 7;
              break l;
            case 4:
              il = 5;
              break l;
            case 7:
              Fo(n) ? (il = 0, rt = null, Fv(t)) : (il = 0, rt = null, le(l, t, n, 7));
              break;
            case 5:
              var i = null;
              switch (J.tag) {
                case 26:
                  i = J.memoizedState;
                case 5:
                case 27:
                  var c = J;
                  if (i ? Gd(i) : c.stateNode.complete) {
                    il = 0, rt = null;
                    var f = c.sibling;
                    if (f !== null) J = f;
                    else {
                      var h = c.return;
                      h !== null ? (J = h, bi(h)) : J = null;
                    }
                    break t;
                  }
              }
              il = 0, rt = null, le(l, t, n, 5);
              break;
            case 6:
              il = 0, rt = null, le(l, t, n, 6);
              break;
            case 8:
              Yf(), Tl = 6;
              break l;
            default:
              throw Error(r(462));
          }
        }
        Wh();
        break;
      } catch (S) {
        Zv(l, S);
      }
    while (!0);
    return sa = eu = null, M.H = u, M.A = e, ul = a, J !== null ? 0 : (vl = null, F = 0, Dn(), Tl);
  }
  function Wh() {
    for (; J !== null && !mm(); )
      wv(J);
  }
  function wv(l) {
    var t = sv(l.alternate, l, ra);
    l.memoizedProps = l.pendingProps, t === null ? bi(l) : J = t;
  }
  function Fv(l) {
    var t = l, a = t.alternate;
    switch (t.tag) {
      case 15:
      case 0:
        t = av(
          a,
          t,
          t.pendingProps,
          t.type,
          void 0,
          F
        );
        break;
      case 11:
        t = av(
          a,
          t,
          t.pendingProps,
          t.type.render,
          t.ref,
          F
        );
        break;
      case 5:
        Zc(t);
        var u = t;
        u === Rl && (L ? (xn(u), u.tag === 5 && u.stateNode != null && (hl = u.stateNode)) : (xn(u), L = !0));
      default:
        vv(a, t), t = J = qo(t, ra), t = sv(a, t, ra);
    }
    l.memoizedProps = l.pendingProps, t === null ? bi(l) : J = t;
  }
  function le(l, t, a, u) {
    sa = eu = null, Zc(t), Gu = null, Ue = 0;
    var e = t.return;
    try {
      if (qh(
        l,
        e,
        t,
        a,
        F
      )) {
        Tl = 1, Pn(
          l,
          _t(a, l.current)
        ), J = null;
        return;
      }
    } catch (n) {
      if (e !== null) throw J = e, n;
      Tl = 1, Pn(
        l,
        _t(a, l.current)
      ), J = null;
      return;
    }
    t.flags & 32768 ? (L || u === 1 ? l = !0 : wu || (F & 536870912) !== 0 ? l = !1 : (Ya = l = !0, (u === 2 || u === 9 || u === 3 || u === 6) && (u = Gl.current, u !== null && u.tag === 13 && (u.flags |= 16384))), $v(t, l)) : bi(t);
  }
  function bi(l) {
    var t = l;
    do {
      if ((t.flags & 32768) !== 0) {
        $v(
          t,
          Ya
        );
        return;
      }
      l = t.return;
      var a = Qh(
        t.alternate,
        t,
        ra
      );
      if (a !== null) {
        J = a;
        return;
      }
      if (t = t.sibling, t !== null) {
        J = t;
        return;
      }
      J = t = l;
    } while (t !== null);
    Tl === 0 && (Tl = 5);
  }
  function $v(l, t) {
    do {
      var a = Vh(l.alternate, l);
      if (a !== null) {
        a.flags &= 32767, J = a;
        return;
      }
      if (a = l.return, a !== null && (a.flags |= 32768, a.subtreeFlags = 0, a.deletions = null), !t && (l = l.sibling, l !== null)) {
        J = l;
        return;
      }
      J = l = a;
    } while (l !== null);
    Tl = 6, J = null;
  }
  function Wv(l, t, a, u, e, n, i, c, f, h, S, b) {
    l.cancelPendingCommit = null;
    do
      zi();
    while (gl !== 0);
    if ((ul & 6) !== 0) throw Error(r(327));
    if (t !== null) {
      if (t === l.current) throw Error(r(177));
      l === vl && (J = vl = null, F = 0), gu = t, Yt = l, It = a, pf = e, Gv = u, kh(
        l,
        t,
        a,
        i,
        c,
        f,
        b
      );
    }
  }
  function kh(l, t, a, u, e, n, i) {
    var c = t.lanes | t.childLanes;
    if (Hf = c, c |= gc, Nm(
      l,
      a,
      c,
      u,
      e,
      n
    ), Wu = null, (a & 335544064) === a ? (ku = Oh(l), u = 10262) : (ku = null, u = 10256), (t.subtreeFlags & u) !== 0 || (t.flags & u) !== 0 ? (l.callbackNode = null, l.callbackPriority = 0, ur(on, function() {
      return Qf(), null;
    })) : (l.callbackNode = null, l.callbackPriority = 0), ci = !1, u = (t.flags & 13878) !== 0, (t.subtreeFlags & 13878) !== 0 || u) {
      u = M.T, M.T = null, e = G.p, G.p = 2, n = ul, ul |= 4;
      try {
        Zh(l, t, a);
      } finally {
        ul = n, G.p = e, M.T = u;
      }
    }
    gl = 1, ci ? $u = Nr(
      i,
      l.containerInfo,
      ku,
      Bf,
      Gf,
      Ph,
      Xf,
      Qf,
      Ih
    ) : (Bf(), Gf(), Xf());
  }
  function Ih(l) {
    if (gl !== 0) {
      var t = Yt.onRecoverableError;
      t(l, { componentStack: null });
    }
  }
  function Ph() {
    gl === 3 && (gl = 0, Rv(gu, Yt), gl = 4);
  }
  function Bf() {
    if (gl === 1) {
      gl = 0;
      var l = Yt, t = gu, a = It, u = (t.flags & 13878) !== 0;
      if ((t.subtreeFlags & 13878) !== 0 || u) {
        u = M.T, M.T = null;
        var e = G.p;
        G.p = 2;
        var n = ul;
        ul |= 4;
        try {
          Ve = oi = !1, Uv(t, l, a), a = Pf;
          var i = Do(l.containerInfo), c = a.focusedElem, f = a.selectionRange;
          if (i !== c && c && c.ownerDocument && Ao(
            c.ownerDocument.documentElement,
            c
          )) {
            if (f !== null && dc(c)) {
              var h = f.start, S = f.end;
              if (S === void 0 && (S = h), "selectionStart" in c)
                c.selectionStart = h, c.selectionEnd = Math.min(
                  S,
                  c.value.length
                );
              else {
                var b = c.ownerDocument || document, d = b && b.defaultView || window;
                if (d.getSelection) {
                  var g = d.getSelection(), _ = c.textContent.length, D = Math.min(f.start, _), Z = f.end === void 0 ? D : Math.min(f.end, _);
                  !g.extend && D > Z && (i = Z, Z = D, D = i);
                  var m = Oo(
                    c,
                    D
                  ), s = Oo(
                    c,
                    Z
                  );
                  if (m && s && (g.rangeCount !== 1 || g.anchorNode !== m.node || g.anchorOffset !== m.offset || g.focusNode !== s.node || g.focusOffset !== s.offset)) {
                    var y = b.createRange();
                    y.setStart(m.node, m.offset), g.removeAllRanges(), D > Z ? (g.addRange(y), g.extend(s.node, s.offset)) : (y.setEnd(s.node, s.offset), g.addRange(y));
                  }
                }
              }
            }
            for (b = [], g = c; g = g.parentNode; )
              g.nodeType === 1 && b.push({
                element: g,
                left: g.scrollLeft,
                top: g.scrollTop
              });
            for (typeof c.focus == "function" && c.focus(), c = 0; c < b.length; c++) {
              var T = b[c];
              T.element.scrollLeft = T.left, T.element.scrollTop = T.top;
            }
          }
          fe = !!If, Pf = If = null;
        } finally {
          ul = n, G.p = e, M.T = u;
        }
      }
      l.current = t, gl = 2;
    }
  }
  function Gf() {
    if (gl === 2) {
      gl = 0;
      var l = Yt, t = gu, a = (t.flags & 8772) !== 0;
      if ((t.subtreeFlags & 8772) !== 0 || a) {
        a = M.T, M.T = null;
        var u = G.p;
        G.p = 2;
        var e = ul;
        ul |= 4;
        try {
          _v(l, t.alternate, t);
        } finally {
          ul = e, G.p = u, M.T = a;
        }
      }
      gl = 3;
    }
  }
  function Xf() {
    if (gl === 4 || gl === 3) {
      gl = 0;
      var l = $u;
      $u = null, hm();
      var t = Yt, a = gu, u = It, e = Gv, n = (u & 335544064) === u ? 10262 : 10256;
      if ((a.subtreeFlags & n) !== 0 || (a.flags & n) !== 0 ? gl = 5 : (gl = 0, gu = Yt = null, kv(t, t.pendingLanes)), n = t.pendingLanes, n === 0 && (Ba = null), Fi(u), a = a.stateNode, st && typeof st.onCommitFiberRoot == "function")
        try {
          st.onCommitFiberRoot(
            ve,
            a,
            void 0,
            (a.current.flags & 128) === 128
          );
        } catch {
        }
      if (e !== null) {
        a = M.T, n = G.p, G.p = 2, M.T = null;
        try {
          for (var i = t.onRecoverableError, c = 0; c < e.length; c++) {
            var f = e[c];
            i(f.value, {
              componentStack: f.stack
            });
          }
        } finally {
          M.T = a, G.p = n;
        }
      }
      if (e = Wu, i = ku, ku = null, e !== null && (Wu = null, i === null && (i = []), l !== null))
        for (f = 0; f < e.length; f++)
          a = (0, e[f])(
            i
          ), a !== void 0 && l.finished.finally(a);
      (It & 3) !== 0 && zi(), Pt(t), n = t.pendingLanes, (u & 261930) !== 0 && (n & 42) !== 0 ? t === Si ? Je++ : (Je = 0, Si = t) : (Je = 0, Si = null), we(0);
    }
  }
  function kv(l, t) {
    (l.pooledCacheLanes &= t) === 0 && (t = l.pooledCache, t != null && (l.pooledCache = null, De(t)));
  }
  function zi() {
    return $u !== null && ($u.skipTransition(), $u = null), Bf(), Gf(), Xf(), Qf();
  }
  function Qf() {
    if (gl !== 5) return !1;
    var l = Yt, t = Hf;
    Hf = 0;
    var a = Fi(It), u = M.T, e = G.p;
    try {
      G.p = 32 > a ? 32 : a, M.T = null, a = pf, pf = null;
      var n = Yt, i = It;
      if (gl = 0, gu = Yt = null, It = 0, (ul & 6) !== 0) throw Error(r(331));
      var c = ul;
      if (ul |= 4, Yv(n.current), xv(
        n,
        n.current,
        i,
        a
      ), ul = c, we(0, !1), st && typeof st.onPostCommitFiberRoot == "function")
        try {
          st.onPostCommitFiberRoot(ve, n);
        } catch {
        }
      return !0;
    } finally {
      G.p = e, M.T = u, kv(l, t);
    }
  }
  function Iv(l, t, a) {
    t = _t(a, t), t = uf(l.stateNode, t, 2), l = Ua(l, t, 2), l !== null && (me(l, 2), Pt(l));
  }
  function cl(l, t, a) {
    if (l.tag === 3)
      Iv(l, l, a);
    else
      for (; t !== null; ) {
        if (t.tag === 3) {
          Iv(
            t,
            l,
            a
          );
          break;
        } else if (t.tag === 1) {
          var u = t.stateNode;
          if (typeof t.type.getDerivedStateFromError == "function" || typeof u.componentDidCatch == "function" && (Ba === null || !Ba.has(u))) {
            l = _t(a, l), a = F0(2), u = Ua(t, a, 2), u !== null && ($0(
              a,
              u,
              t,
              l
            ), me(u, 2), Pt(u));
            break;
          }
        }
        t = t.return;
      }
  }
  function Vf(l, t, a) {
    var u = l.pingCache;
    if (u === null) {
      u = l.pingCache = new Jh();
      var e = /* @__PURE__ */ new Set();
      u.set(t, e);
    } else
      e = u.get(t), e === void 0 && (e = /* @__PURE__ */ new Set(), u.set(t, e));
    e.has(a) || (Rf = !0, e.add(a), l = lr.bind(null, l, t, a), t.then(l, l));
  }
  function lr(l, t, a) {
    var u = l.pingCache;
    u !== null && u.delete(t), l.pingedLanes |= l.suspendedLanes & a, l.warmLanes &= ~a, vl === l && (F & a) === a && ((Tl === 4 || Tl === 3 && (F & 62914560) === F && 300 > ft() - ri) && (ul & 2) === 0 ? Pu(l, 0) : hi |= a, Fu === F && (Fu = 0)), Pt(l);
  }
  function Pv(l, t) {
    t === 0 && (t = qs()), l = tu(l, t), l !== null && (me(l, t), Pt(l));
  }
  function tr(l) {
    var t = l.memoizedState, a = 0;
    t !== null && (a = t.retryLane), Pv(l, a);
  }
  function ar(l, t) {
    var a = 0;
    switch (l.tag) {
      case 31:
      case 13:
        var u = l.stateNode, e = l.memoizedState;
        e !== null && (a = e.retryLane);
        break;
      case 19:
        u = l.stateNode;
        break;
      case 22:
        u = l.stateNode._retryCache;
        break;
      default:
        throw Error(r(314));
    }
    u !== null && u.delete(t), Pv(l, a);
  }
  function ur(l, t) {
    return Li(l, t);
  }
  var te = null, ae = null, Zf = !1, Ni = !1, Lf = !1, Xa = 0;
  function Pt(l) {
    l !== ae && l.next === null && (ae === null ? te = ae = l : ae = ae.next = l), Ni = !0, Zf || (Zf = !0, nr());
  }
  function we(l, t) {
    if (!Lf && Ni) {
      Lf = !0;
      do
        for (var a = !1, u = te; u !== null; ) {
          if (l !== 0) {
            var e = u.pendingLanes;
            if (e === 0) var n = 0;
            else {
              var i = u.suspendedLanes, c = u.pingedLanes;
              n = (1 << 31 - ot(42 | l) + 1) - 1, n &= e & ~(i & ~c), n = n & 201326741 ? n & 201326741 | 1 : n ? n | 2 : 0;
            }
            n !== 0 && (a = !0, ud(u, n));
          } else
            n = F, n = hn(
              u,
              u === vl ? n : 0,
              u.cancelPendingCommit !== null || u.timeoutHandle !== -1
            ), (n & 3) === 0 || de(u, n) || (a = !0, ud(u, n));
          u = u.next;
        }
      while (a);
      Lf = !1;
    }
  }
  function er() {
    ld();
  }
  function ld() {
    Ni = Zf = !1;
    var l = 0;
    Xa !== 0 && yr() && (l = Xa);
    for (var t = ft(), a = null, u = te; u !== null; ) {
      var e = u.next, n = td(u, t);
      n === 0 ? (u.next = null, a === null ? te = e : a.next = e, e === null && (ae = a)) : (a = u, (l !== 0 || (n & 3) !== 0) && (Ni = !0)), u = e;
    }
    gl !== 0 && gl !== 5 || we(l), Xa !== 0 && (Xa = 0);
  }
  function td(l, t) {
    for (var a = l.suspendedLanes, u = l.pingedLanes, e = l.expirationTimes, n = l.pendingLanes & -62914561; 0 < n; ) {
      var i = 31 - ot(n), c = 1 << i, f = e[i];
      f === -1 ? ((c & a) === 0 || (c & u) !== 0) && (e[i] = zm(c, t)) : f <= t && (l.expiredLanes |= c), n &= ~c;
    }
    if (t = vl, a = F, a = hn(
      l,
      l === t ? a : 0,
      l.cancelPendingCommit !== null || l.timeoutHandle !== -1
    ), u = l.callbackNode, a === 0 || l === t && (il === 2 || il === 9) || l.cancelPendingCommit !== null)
      return u !== null && u !== null && Ki(u), l.callbackNode = null, l.callbackPriority = 0;
    if ((a & 3) === 0 || de(l, a)) {
      if (t = a & -a, t === l.callbackPriority) return t;
      switch (u !== null && Ki(u), Fi(a)) {
        case 2:
        case 8:
          a = Hs;
          break;
        case 32:
          a = on;
          break;
        case 268435456:
          a = ps;
          break;
        default:
          a = on;
      }
      return u = ad.bind(null, l), a = Li(a, u), l.callbackPriority = t, l.callbackNode = a, t;
    }
    return u !== null && u !== null && Ki(u), l.callbackPriority = 2, l.callbackNode = null, 2;
  }
  function ad(l, t) {
    if (gl !== 0 && gl !== 5)
      return l.callbackNode = null, l.callbackPriority = 0, null;
    var a = l.callbackNode;
    if (zi() && l.callbackNode !== a)
      return null;
    var u = F;
    return u = hn(
      l,
      l === vl ? u : 0,
      l.cancelPendingCommit !== null || l.timeoutHandle !== -1
    ), u === 0 ? null : (Qv(l, u, t), td(l, ft()), l.callbackNode != null && l.callbackNode === a ? ad.bind(null, l) : null);
  }
  function ud(l, t) {
    if (zi()) return null;
    Qv(l, t, !0);
  }
  function nr() {
    Sr(function() {
      (ul & 6) !== 0 ? Li(
        xs,
        er
      ) : ld();
    });
  }
  function Kf() {
    if (Xa === 0) {
      var l = cu;
      l === 0 && (l = vn, vn <<= 1, (vn & 261888) === 0 && (vn = 256)), Xa = l;
    }
    return Xa;
  }
  function ed(l) {
    return l == null || typeof l == "symbol" || typeof l == "boolean" ? null : typeof l == "function" ? l : Tn(l);
  }
  function ir(l, t, a, u, e) {
    if (t === "submit" && a && a.stateNode === e) {
      var n = ed(
        (e[kl] || null).action
      ), i = u.submitter;
      i && (t = (t = i[kl] || null) ? ed(t.formAction) : i.getAttribute("formAction"), t !== null && (n = t, i = null));
      var c = new Nn(
        "action",
        "action",
        null,
        u,
        e
      );
      l.push({
        event: c,
        listeners: [
          {
            instance: null,
            listener: function() {
              if (u.defaultPrevented) {
                if (Xa !== 0) {
                  var f = new FormData(e, i);
                  Ic(
                    a,
                    {
                      pending: !0,
                      data: f,
                      method: e.method,
                      action: n
                    },
                    null,
                    f
                  );
                }
              } else
                typeof n == "function" && (c.preventDefault(), f = new FormData(e, i), Ic(
                  a,
                  {
                    pending: !0,
                    data: f,
                    method: e.method,
                    action: n
                  },
                  n,
                  f
                ));
            },
            currentTarget: e
          }
        ]
      });
    }
  }
  for (var Jf = 0; Jf < yc.length; Jf++) {
    var wf = yc[Jf], cr = wf.toLowerCase(), fr = wf[0].toUpperCase() + wf.slice(1);
    Rt(
      cr,
      "on" + fr
    );
  }
  Rt(Uo, "onAnimationEnd"), Rt(Co, "onAnimationIteration"), Rt(Ro, "onAnimationStart"), Rt("dblclick", "onDoubleClick"), Rt("focusin", "onFocus"), Rt("focusout", "onBlur"), Rt(gh, "onTransitionRun"), Rt(Sh, "onTransitionStart"), Rt(Th, "onTransitionCancel"), Rt(xo, "onTransitionEnd"), _u("onMouseEnter", ["mouseout", "mouseover"]), _u("onMouseLeave", ["mouseout", "mouseover"]), _u("onPointerEnter", ["pointerout", "pointerover"]), _u("onPointerLeave", ["pointerout", "pointerover"]), Ia(
    "onChange",
    "change click focusin focusout input keydown keyup selectionchange".split(" ")
  ), Ia(
    "onSelect",
    "focusout contextmenu dragend focusin keydown keyup mousedown mouseup selectionchange".split(
      " "
    )
  ), Ia("onBeforeInput", [
    "compositionend",
    "keypress",
    "textInput",
    "paste"
  ]), Ia(
    "onCompositionEnd",
    "compositionend focusout keydown keypress keyup mousedown".split(" ")
  ), Ia(
    "onCompositionStart",
    "compositionstart focusout keydown keypress keyup mousedown".split(" ")
  ), Ia(
    "onCompositionUpdate",
    "compositionupdate focusout keydown keypress keyup mousedown".split(" ")
  );
  var Fe = "abort canplay canplaythrough durationchange emptied encrypted ended error loadeddata loadedmetadata loadstart pause play playing progress ratechange resize seeked seeking stalled suspend timeupdate volumechange waiting".split(
    " "
  ), sr = new Set(
    "beforetoggle cancel close invalid load scroll scrollend toggle".split(" ").concat(Fe)
  );
  function nd(l, t) {
    t = (t & 4) !== 0;
    for (var a = 0; a < l.length; a++) {
      var u = l[a], e = u.event;
      u = u.listeners;
      l: {
        var n = void 0;
        if (t)
          for (var i = u.length - 1; 0 <= i; i--) {
            var c = u[i], f = c.instance, h = c.currentTarget;
            if (c = c.listener, f !== n && e.isPropagationStopped())
              break l;
            n = c, e.currentTarget = h;
            try {
              n(e);
            } catch (S) {
              An(S);
            }
            e.currentTarget = null, n = f;
          }
        else
          for (i = 0; i < u.length; i++) {
            if (c = u[i], f = c.instance, h = c.currentTarget, c = c.listener, f !== n && e.isPropagationStopped())
              break l;
            n = c, e.currentTarget = h;
            try {
              n(e);
            } catch (S) {
              An(S);
            }
            e.currentTarget = null, n = f;
          }
      }
    }
  }
  function w(l, t) {
    var a = t[Zs];
    a === void 0 && (a = t[Zs] = /* @__PURE__ */ new Set());
    var u = l + "__bubble";
    a.has(u) || (id(t, l, 2, !1), a.add(u));
  }
  function Ff(l, t, a) {
    var u = 0;
    t && (u |= 4), id(
      a,
      l,
      u,
      t
    );
  }
  var _i = "_reactListening" + Math.random().toString(36).slice(2);
  function $f(l) {
    if (!l[_i]) {
      l[_i] = !0, Js.forEach(function(a) {
        a !== "selectionchange" && (sr.has(a) || Ff(a, !1, l), Ff(a, !0, l));
      });
      var t = l.nodeType === 9 ? l : l.ownerDocument;
      t === null || t[_i] || (t[_i] = !0, Ff("selectionchange", !1, t));
    }
  }
  function id(l, t, a, u) {
    switch ($d(t)) {
      case 2:
        var e = ty;
        break;
      case 8:
        e = ay;
        break;
      default:
        e = rs;
    }
    a = e.bind(
      null,
      t,
      a,
      l
    ), e = void 0, !ac || t !== "touchstart" && t !== "touchmove" && t !== "wheel" || (e = !0), u ? e !== void 0 ? l.addEventListener(t, a, {
      capture: !0,
      passive: e
    }) : l.addEventListener(t, a, !0) : e !== void 0 ? l.addEventListener(t, a, {
      passive: e
    }) : l.addEventListener(t, a, !1);
  }
  function Wf(l, t, a, u, e) {
    var n = u;
    if ((t & 1) === 0 && (t & 2) === 0 && u !== null)
      l: for (; ; ) {
        if (u === null) return;
        var i = u.tag;
        if (i === 3 || i === 4) {
          var c = u.stateNode.containerInfo;
          if (c === e) break;
          if (i === 4)
            for (i = u.return; i !== null; ) {
              var f = i.tag;
              if ((f === 3 || f === 4) && i.stateNode.containerInfo === e)
                return;
              i = i.return;
            }
          for (; c !== null; ) {
            if (i = ka(c), i === null) return;
            if (f = i.tag, f === 5 || f === 6 || f === 26 || f === 27) {
              u = n = i;
              continue l;
            }
            c = c.parentNode;
          }
        }
        u = u.return;
      }
    no(function() {
      var h = n, S = lc(a), b = [];
      l: {
        var d = Ho.get(l);
        if (d !== void 0) {
          var g = Nn, _ = l;
          switch (l) {
            case "keypress":
              if (bn(a) === 0) break l;
            case "keydown":
            case "keyup":
              g = wm;
              break;
            case "focusin":
              _ = "focus", g = ic;
              break;
            case "focusout":
              _ = "blur", g = ic;
              break;
            case "beforeblur":
            case "afterblur":
              g = ic;
              break;
            case "click":
              if (a.button === 2) break l;
            case "auxclick":
            case "dblclick":
            case "mousedown":
            case "mousemove":
            case "mouseup":
            case "mouseout":
            case "mouseover":
            case "contextmenu":
              g = fo;
              break;
            case "drag":
            case "dragend":
            case "dragenter":
            case "dragexit":
            case "dragleave":
            case "dragover":
            case "dragstart":
            case "drop":
              g = pm;
              break;
            case "touchcancel":
            case "touchend":
            case "touchmove":
            case "touchstart":
              g = Im;
              break;
            case Uo:
            case Co:
            case Ro:
              g = Bm;
              break;
            case xo:
              g = lh;
              break;
            case "scroll":
            case "scrollend":
              g = xm;
              break;
            case "wheel":
              g = ah;
              break;
            case "copy":
            case "cut":
            case "paste":
              g = Xm;
              break;
            case "gotpointercapture":
            case "lostpointercapture":
            case "pointercancel":
            case "pointerdown":
            case "pointermove":
            case "pointerout":
            case "pointerover":
            case "pointerup":
              g = oo;
              break;
            case "submit":
              g = Wm;
              break;
            case "toggle":
            case "beforetoggle":
              g = eh;
          }
          var D = (t & 4) !== 0, Z = !D && (l === "scroll" || l === "scrollend"), m = D ? d !== null ? d + "Capture" : null : d;
          D = [];
          for (var s = h, y; s !== null; ) {
            var T = s;
            if (y = T.stateNode, T = T.tag, T !== 5 && T !== 26 && T !== 27 || y === null || m === null || (T = ye(s, m), T != null && D.push(
              $e(s, T, y)
            )), Z) break;
            s = s.return;
          }
          0 < D.length && (d = new g(
            d,
            _,
            null,
            a,
            S
          ), b.push({ event: d, listeners: D }));
        }
      }
      if ((t & 7) === 0) {
        l: {
          if (g = l === "mouseover" || l === "pointerover", d = l === "mouseout" || l === "pointerout", g && a !== Pi && (_ = a.relatedTarget || a.fromElement) && (ka(_) || _[bu]))
            break l;
          (d || g) && (_ = S.window === S ? S : (g = S.ownerDocument) ? g.defaultView || g.parentWindow : window, d ? (g = a.relatedTarget || a.toElement, d = h, g = g ? ka(g) : null, g !== null && (Z = U(g), D = g.tag, g !== Z || D !== 5 && D !== 27 && D !== 6) && (g = null)) : (d = null, g = h), d !== g && (D = fo, T = "onMouseLeave", m = "onMouseEnter", s = "mouse", (l === "pointerout" || l === "pointerover") && (D = oo, T = "onPointerLeave", m = "onPointerEnter", s = "pointer"), Z = d == null ? _ : re(d), y = g == null ? _ : re(g), _ = new D(
            T,
            s + "leave",
            d,
            a,
            S
          ), _.target = Z, _.relatedTarget = y, T = null, ka(S) === h && (D = new D(
            m,
            s + "enter",
            g,
            a,
            S
          ), D.target = y, D.relatedTarget = Z, T = D), Z = T, D = d && g ? it(
            d,
            g,
            or
          ) : null, d !== null && cd(
            b,
            _,
            d,
            D,
            !1
          ), g !== null && Z !== null && cd(
            b,
            Z,
            g,
            D,
            !0
          )));
        }
        l: {
          if (d = h ? re(h) : window, g = d.nodeName && d.nodeName.toLowerCase(), g === "select" || g === "input" && d.type === "file")
            var A = To;
          else if (go(d))
            if (Eo)
              A = hh;
            else {
              A = dh;
              var $ = vh;
            }
          else
            g = d.nodeName, !g || g.toLowerCase() !== "input" || d.type !== "checkbox" && d.type !== "radio" ? h && Ii(h.elementType) && (A = To) : A = mh;
          if (A && (A = A(l, h))) {
            So(
              b,
              A,
              a,
              S
            );
            break l;
          }
          $ && $(l, d, h);
        }
        switch ($ = h ? re(h) : window, l) {
          case "focusin":
            (go($) || $.contentEditable === "true") && (Uu = $, mc = h, _e = null);
            break;
          case "focusout":
            _e = mc = Uu = null;
            break;
          case "mousedown":
            hc = !0;
            break;
          case "contextmenu":
          case "mouseup":
          case "dragend":
            hc = !1, Mo(b, a, S);
            break;
          case "selectionchange":
            if (yh) break;
          case "keydown":
          case "keyup":
            Mo(b, a, S);
        }
        var j;
        if (fc)
          l: {
            switch (l) {
              case "compositionstart":
                var x = "onCompositionStart";
                break l;
              case "compositionend":
                x = "onCompositionEnd";
                break l;
              case "compositionupdate":
                x = "onCompositionUpdate";
                break l;
            }
            x = void 0;
          }
        else
          ju ? ro(l, a) && (x = "onCompositionEnd") : l === "keydown" && a.keyCode === 229 && (x = "onCompositionStart");
        x && (vo && a.locale !== "ko" && (ju || x !== "onCompositionStart" ? x === "onCompositionEnd" && ju && (j = io()) : (ba = S, uc = "value" in ba ? ba.value : ba.textContent, ju = !0)), $ = Oi(h, x), 0 < $.length && (x = new so(
          x,
          l,
          null,
          a,
          S
        ), b.push({ event: x, listeners: $ }), j ? x.data = j : (j = yo(a), j !== null && (x.data = j)))), (j = ih ? ch(l, a) : fh(l, a)) && (x = Oi(h, "onBeforeInput"), 0 < x.length && ($ = new so(
          "onBeforeInput",
          "beforeinput",
          null,
          a,
          S
        ), b.push({
          event: $,
          listeners: x
        }), $.data = j)), ir(
          b,
          l,
          h,
          a,
          S
        );
      }
      nd(b, t);
    });
  }
  function $e(l, t, a) {
    return {
      instance: l,
      listener: t,
      currentTarget: a
    };
  }
  function Oi(l, t) {
    for (var a = t + "Capture", u = []; l !== null; ) {
      var e = l, n = e.stateNode;
      if (e = e.tag, e !== 5 && e !== 26 && e !== 27 || n === null || (e = ye(l, a), e != null && u.unshift(
        $e(l, e, n)
      ), e = ye(l, t), e != null && u.push(
        $e(l, e, n)
      )), l.tag === 3) return u;
      l = l.return;
    }
    return [];
  }
  function or(l) {
    if (l === null) return null;
    do
      l = l.return;
    while (l && l.tag !== 5 && l.tag !== 27);
    return l || null;
  }
  function cd(l, t, a, u, e) {
    for (var n = t._reactName, i = []; a !== null && a !== u; ) {
      var c = a, f = c.alternate, h = c.stateNode;
      if (c = c.tag, f !== null && f === u) break;
      c !== 5 && c !== 26 && c !== 27 || h === null || (f = h, e ? (h = ye(a, n), h != null && i.unshift(
        $e(a, h, f)
      )) : e || (h = ye(a, n), h != null && i.push(
        $e(a, h, f)
      ))), a = a.return;
    }
    i.length !== 0 && l.push({ event: t, listeners: i });
  }
  var vr = /\r\n?/g, dr = /\u0000|\uFFFD/g;
  function fd(l) {
    return (typeof l == "string" ? l : "" + l).replace(vr, `
`).replace(dr, "");
  }
  function sd(l, t) {
    return t = fd(t), fd(l) === t;
  }
  function fl(l, t, a, u, e, n) {
    switch (a) {
      case "children":
        if (typeof u == "string")
          t === "body" || t === "textarea" && u === "" || Au(l, u);
        else if (typeof u == "number" || typeof u == "bigint")
          t !== "body" && Au(l, "" + u);
        else return;
        break;
      case "className":
        Sn(l, "class", u);
        break;
      case "tabIndex":
        Sn(l, "tabindex", u);
        break;
      case "dir":
      case "role":
      case "viewBox":
      case "width":
      case "height":
        Sn(l, a, u);
        break;
      case "style":
        uo(l, u, n);
        return;
      case "data":
        if (t !== "object") {
          Sn(l, "data", u);
          break;
        }
      case "src":
      case "href":
        if (u === "" && (t !== "a" || a !== "href")) {
          l.removeAttribute(a);
          break;
        }
        if (u == null || typeof u == "function" || typeof u == "symbol" || typeof u == "boolean") {
          l.removeAttribute(a);
          break;
        }
        u = Tn(u), l.setAttribute(a, u);
        break;
      case "action":
      case "formAction":
        if (typeof u == "function") {
          l.setAttribute(
            a,
            "javascript:throw new Error('A React form was unexpectedly submitted. If you called form.submit() manually, consider using form.requestSubmit() instead. If you\\'re trying to use event.stopPropagation() in a submit event handler, consider also calling event.preventDefault().')"
          );
          break;
        } else
          typeof n == "function" && (a === "formAction" ? (t !== "input" && fl(l, t, "name", e.name, e, null), fl(
            l,
            t,
            "formEncType",
            e.formEncType,
            e,
            null
          ), fl(
            l,
            t,
            "formMethod",
            e.formMethod,
            e,
            null
          ), fl(
            l,
            t,
            "formTarget",
            e.formTarget,
            e,
            null
          )) : (fl(l, t, "encType", e.encType, e, null), fl(l, t, "method", e.method, e, null), fl(l, t, "target", e.target, e, null)));
        if (u == null || typeof u == "symbol" || typeof u == "boolean") {
          l.removeAttribute(a);
          break;
        }
        u = Tn(u), l.setAttribute(a, u);
        break;
      case "onClick":
        u != null && (l.onclick = Zt);
        return;
      case "onScroll":
        u != null && w("scroll", l);
        return;
      case "onScrollEnd":
        u != null && w("scrollend", l);
        return;
      case "dangerouslySetInnerHTML":
        if (u != null) {
          if (typeof u != "object" || !("__html" in u))
            throw Error(r(61));
          if (a = u.__html, a != null) {
            if (e.children != null) throw Error(r(60));
            n?.__html !== a && (l.innerHTML = a);
          }
        }
        break;
      case "multiple":
        l.multiple = u && typeof u != "function" && typeof u != "symbol";
        break;
      case "muted":
        l.muted = u && typeof u != "function" && typeof u != "symbol";
        break;
      case "suppressContentEditableWarning":
      case "suppressHydrationWarning":
      case "defaultValue":
      case "defaultChecked":
      case "innerHTML":
      case "ref":
        break;
      case "autoFocus":
        break;
      case "xlinkHref":
        if (u == null || typeof u == "function" || typeof u == "boolean" || typeof u == "symbol") {
          l.removeAttribute("xlink:href");
          break;
        }
        a = Tn(u), l.setAttributeNS(
          "http://www.w3.org/1999/xlink",
          "xlink:href",
          a
        );
        break;
      case "contentEditable":
      case "spellCheck":
      case "draggable":
      case "value":
      case "autoReverse":
      case "externalResourcesRequired":
      case "focusable":
      case "preserveAlpha":
        u != null && typeof u != "function" && typeof u != "symbol" ? l.setAttribute(a, u) : l.removeAttribute(a);
        break;
      case "inert":
      case "allowFullScreen":
      case "async":
      case "autoPlay":
      case "controls":
      case "credentialless":
      case "default":
      case "defer":
      case "disabled":
      case "disablePictureInPicture":
      case "disableRemotePlayback":
      case "formNoValidate":
      case "hidden":
      case "loop":
      case "noModule":
      case "noValidate":
      case "open":
      case "playsInline":
      case "readOnly":
      case "required":
      case "reversed":
      case "scoped":
      case "seamless":
      case "itemScope":
        u && typeof u != "function" && typeof u != "symbol" ? l.setAttribute(a, "") : l.removeAttribute(a);
        break;
      case "capture":
      case "download":
        u === !0 ? l.setAttribute(a, "") : u !== !1 && u != null && typeof u != "function" && typeof u != "symbol" ? l.setAttribute(a, u) : l.removeAttribute(a);
        break;
      case "cols":
      case "rows":
      case "size":
      case "span":
        u != null && typeof u != "function" && typeof u != "symbol" && !isNaN(u) && 1 <= u ? l.setAttribute(a, u) : l.removeAttribute(a);
        break;
      case "rowSpan":
      case "start":
        u == null || typeof u == "function" || typeof u == "symbol" || isNaN(u) ? l.removeAttribute(a) : l.setAttribute(a, u);
        break;
      case "popover":
        w("beforetoggle", l), w("toggle", l), gn(l, "popover", u);
        break;
      case "xlinkActuate":
        ua(
          l,
          "http://www.w3.org/1999/xlink",
          "xlink:actuate",
          u
        );
        break;
      case "xlinkArcrole":
        ua(
          l,
          "http://www.w3.org/1999/xlink",
          "xlink:arcrole",
          u
        );
        break;
      case "xlinkRole":
        ua(
          l,
          "http://www.w3.org/1999/xlink",
          "xlink:role",
          u
        );
        break;
      case "xlinkShow":
        ua(
          l,
          "http://www.w3.org/1999/xlink",
          "xlink:show",
          u
        );
        break;
      case "xlinkTitle":
        ua(
          l,
          "http://www.w3.org/1999/xlink",
          "xlink:title",
          u
        );
        break;
      case "xlinkType":
        ua(
          l,
          "http://www.w3.org/1999/xlink",
          "xlink:type",
          u
        );
        break;
      case "xmlBase":
        ua(
          l,
          "http://www.w3.org/XML/1998/namespace",
          "xml:base",
          u
        );
        break;
      case "xmlLang":
        ua(
          l,
          "http://www.w3.org/XML/1998/namespace",
          "xml:lang",
          u
        );
        break;
      case "xmlSpace":
        ua(
          l,
          "http://www.w3.org/XML/1998/namespace",
          "xml:space",
          u
        );
        break;
      case "is":
        gn(l, "is", u);
        break;
      case "innerText":
      case "textContent":
        return;
      default:
        if (!(2 < a.length) || a[0] !== "o" && a[0] !== "O" || a[1] !== "n" && a[1] !== "N")
          a = Cm.get(a) || a, gn(l, a, u);
        else return;
    }
    ll = !0;
  }
  function kf(l, t, a, u, e, n) {
    switch (a) {
      case "style":
        uo(l, u, n);
        return;
      case "dangerouslySetInnerHTML":
        if (u != null) {
          if (typeof u != "object" || !("__html" in u))
            throw Error(r(61));
          if (a = u.__html, a != null) {
            if (e.children != null) throw Error(r(60));
            n?.__html !== a && (l.innerHTML = a);
          }
        }
        break;
      case "children":
        if (typeof u == "string") Au(l, u);
        else if (typeof u == "number" || typeof u == "bigint")
          Au(l, "" + u);
        else return;
        break;
      case "onScroll":
        u != null && w("scroll", l);
        return;
      case "onScrollEnd":
        u != null && w("scrollend", l);
        return;
      case "onClick":
        u != null && (l.onclick = Zt);
        return;
      case "suppressContentEditableWarning":
      case "suppressHydrationWarning":
      case "innerHTML":
      case "ref":
        return;
      case "innerText":
      case "textContent":
        return;
      default:
        if (!ws.hasOwnProperty(a))
          l: {
            if (a[0] === "o" && a[1] === "n" && (e = a.endsWith("Capture"), n = a.slice(2, e ? a.length - 7 : void 0), t = l[kl] || null, t = t != null ? t[a] : null, typeof t == "function" && l.removeEventListener(n, t, e), typeof u == "function")) {
              typeof t != "function" && t !== null && (a in l ? l[a] = null : l.hasAttribute(a) && l.removeAttribute(a)), l.addEventListener(n, u, e);
              break l;
            }
            ll = !0, a in l ? l[a] = u : u === !0 ? l.setAttribute(a, "") : gn(l, a, u);
          }
        return;
    }
    ll = !0;
  }
  function Vl(l, t, a) {
    switch (t) {
      case "div":
      case "span":
      case "svg":
      case "path":
      case "a":
      case "g":
      case "p":
      case "li":
        break;
      case "img":
        w("error", l), w("load", l);
        var u = !1, e = !1, n;
        for (n in a)
          if (a.hasOwnProperty(n)) {
            var i = a[n];
            if (i != null)
              switch (n) {
                case "src":
                  u = !0;
                  break;
                case "srcSet":
                  e = !0;
                  break;
                case "children":
                case "dangerouslySetInnerHTML":
                  throw Error(r(137, t));
                default:
                  fl(l, t, n, i, a, null);
              }
          }
        e && fl(l, t, "srcSet", a.srcSet, a, null), u && fl(l, t, "src", a.src, a, null);
        return;
      case "input":
        w("invalid", l);
        var c = n = i = e = null, f = null, h = null;
        for (u in a)
          if (a.hasOwnProperty(u)) {
            var S = a[u];
            if (S != null)
              switch (u) {
                case "name":
                  e = S;
                  break;
                case "type":
                  i = S;
                  break;
                case "checked":
                  f = S;
                  break;
                case "defaultChecked":
                  h = S;
                  break;
                case "value":
                  n = S;
                  break;
                case "defaultValue":
                  c = S;
                  break;
                case "children":
                case "dangerouslySetInnerHTML":
                  if (S != null)
                    throw Error(r(137, t));
                  break;
                default:
                  fl(l, t, u, S, a, null);
              }
          }
        Ps(
          l,
          n,
          c,
          f,
          h,
          i,
          e,
          !1
        );
        return;
      case "select":
        w("invalid", l), u = i = n = null;
        for (e in a)
          if (a.hasOwnProperty(e) && (c = a[e], c != null))
            switch (e) {
              case "value":
                n = c;
                break;
              case "defaultValue":
                i = c;
                break;
              case "multiple":
                u = c;
              default:
                fl(l, t, e, c, a, null);
            }
        t = n, a = i, l.multiple = !!u, t != null ? Ou(l, !!u, t, !1) : a != null && Ou(l, !!u, a, !0);
        return;
      case "textarea":
        w("invalid", l), n = e = u = null;
        for (i in a)
          if (a.hasOwnProperty(i) && (c = a[i], c != null))
            switch (i) {
              case "value":
                u = c;
                break;
              case "defaultValue":
                e = c;
                break;
              case "children":
                n = c;
                break;
              case "dangerouslySetInnerHTML":
                if (c != null) throw Error(r(91));
                break;
              default:
                fl(l, t, i, c, a, null);
            }
        to(l, u, e, n);
        return;
      case "option":
        for (f in a)
          if (a.hasOwnProperty(f) && (u = a[f], u != null))
            switch (f) {
              case "selected":
                l.selected = u && typeof u != "function" && typeof u != "symbol";
                break;
              default:
                fl(l, t, f, u, a, null);
            }
        return;
      case "dialog":
        w("beforetoggle", l), w("toggle", l), w("cancel", l), w("close", l);
        break;
      case "iframe":
      case "object":
        w("load", l);
        break;
      case "video":
      case "audio":
        for (u = 0; u < Fe.length; u++)
          w(Fe[u], l);
        break;
      case "image":
        w("error", l), w("load", l);
        break;
      case "details":
        w("toggle", l);
        break;
      case "embed":
      case "source":
      case "link":
        w("error", l), w("load", l);
      case "area":
      case "base":
      case "br":
      case "col":
      case "hr":
      case "keygen":
      case "meta":
      case "param":
      case "track":
      case "wbr":
      case "menuitem":
        for (h in a)
          if (a.hasOwnProperty(h) && (u = a[h], u != null))
            switch (h) {
              case "children":
              case "dangerouslySetInnerHTML":
                throw Error(r(137, t));
              default:
                fl(l, t, h, u, a, null);
            }
        return;
      default:
        if (Ii(t)) {
          for (S in a)
            a.hasOwnProperty(S) && (u = a[S], u !== void 0 && kf(
              l,
              t,
              S,
              u,
              a,
              void 0
            ));
          return;
        }
    }
    for (c in a)
      a.hasOwnProperty(c) && (u = a[c], u != null && fl(l, t, c, u, a, null));
  }
  var mr = {};
  function hr(l, t, a, u) {
    switch (t) {
      case "div":
      case "span":
      case "svg":
      case "path":
      case "a":
      case "g":
      case "p":
      case "li":
        break;
      case "input":
        var e = null, n = null, i = null, c = null, f = null, h = null, S = null;
        for (g in a) {
          var b = a[g];
          if (a.hasOwnProperty(g) && b != null)
            switch (g) {
              case "checked":
                break;
              case "value":
                break;
              case "defaultValue":
                f = b;
              default:
                u.hasOwnProperty(g) || fl(l, t, g, null, u, b);
            }
        }
        for (var d in u) {
          var g = u[d];
          if (b = a[d], u.hasOwnProperty(d) && (g != null || b != null))
            switch (d) {
              case "type":
                g !== b && (ll = !0), n = g;
                break;
              case "name":
                g !== b && (ll = !0), e = g;
                break;
              case "checked":
                g !== b && (ll = !0), h = g;
                break;
              case "defaultChecked":
                g !== b && (ll = !0), S = g;
                break;
              case "value":
                g !== b && (ll = !0), i = g;
                break;
              case "defaultValue":
                g !== b && (ll = !0), c = g;
                break;
              case "children":
              case "dangerouslySetInnerHTML":
                if (g != null)
                  throw Error(r(137, t));
                break;
              default:
                g !== b && fl(
                  l,
                  t,
                  d,
                  g,
                  u,
                  b
                );
            }
        }
        Wi(
          l,
          i,
          c,
          f,
          h,
          S,
          n,
          e
        );
        return;
      case "select":
        g = i = c = d = null;
        for (n in a)
          if (f = a[n], a.hasOwnProperty(n) && f != null)
            switch (n) {
              case "value":
                break;
              case "multiple":
                g = f;
              default:
                u.hasOwnProperty(n) || fl(
                  l,
                  t,
                  n,
                  null,
                  u,
                  f
                );
            }
        for (e in u)
          if (n = u[e], f = a[e], u.hasOwnProperty(e) && (n != null || f != null))
            switch (e) {
              case "value":
                n !== f && (ll = !0), d = n;
                break;
              case "defaultValue":
                n !== f && (ll = !0), c = n;
                break;
              case "multiple":
                n !== f && (ll = !0), i = n;
              default:
                n !== f && fl(
                  l,
                  t,
                  e,
                  n,
                  u,
                  f
                );
            }
        t = c, a = i, u = g, d != null ? Ou(l, !!a, d, !1) : !!u != !!a && (t != null ? Ou(l, !!a, t, !0) : Ou(l, !!a, a ? [] : "", !1));
        return;
      case "textarea":
        g = d = null;
        for (c in a)
          if (e = a[c], a.hasOwnProperty(c) && e != null && !u.hasOwnProperty(c))
            switch (c) {
              case "value":
                break;
              case "children":
                break;
              default:
                fl(l, t, c, null, u, e);
            }
        for (i in u)
          if (e = u[i], n = a[i], u.hasOwnProperty(i) && (e != null || n != null))
            switch (i) {
              case "value":
                e !== n && (ll = !0), d = e;
                break;
              case "defaultValue":
                e !== n && (ll = !0), g = e;
                break;
              case "children":
                break;
              case "dangerouslySetInnerHTML":
                if (e != null) throw Error(r(91));
                break;
              default:
                e !== n && fl(l, t, i, e, u, n);
            }
        lo(l, d, g);
        return;
      case "option":
        for (var _ in a)
          if (d = a[_], a.hasOwnProperty(_) && d != null && !u.hasOwnProperty(_))
            switch (_) {
              case "selected":
                l.selected = !1;
                break;
              default:
                fl(
                  l,
                  t,
                  _,
                  null,
                  u,
                  d
                );
            }
        for (f in u)
          if (d = u[f], g = a[f], u.hasOwnProperty(f) && d !== g && (d != null || g != null))
            switch (f) {
              case "selected":
                d !== g && (ll = !0), l.selected = d && typeof d != "function" && typeof d != "symbol";
                break;
              default:
                fl(
                  l,
                  t,
                  f,
                  d,
                  u,
                  g
                );
            }
        return;
      case "img":
      case "link":
      case "area":
      case "base":
      case "br":
      case "col":
      case "embed":
      case "hr":
      case "keygen":
      case "meta":
      case "param":
      case "source":
      case "track":
      case "wbr":
      case "menuitem":
        for (var D in a)
          d = a[D], a.hasOwnProperty(D) && d != null && !u.hasOwnProperty(D) && fl(l, t, D, null, u, d);
        for (h in u)
          if (d = u[h], g = a[h], u.hasOwnProperty(h) && d !== g && (d != null || g != null))
            switch (h) {
              case "children":
              case "dangerouslySetInnerHTML":
                if (d != null)
                  throw Error(r(137, t));
                break;
              default:
                fl(
                  l,
                  t,
                  h,
                  d,
                  u,
                  g
                );
            }
        return;
      default:
        if (Ii(t)) {
          for (var Z in a)
            d = a[Z], a.hasOwnProperty(Z) && d !== void 0 && !u.hasOwnProperty(Z) && kf(
              l,
              t,
              Z,
              void 0,
              u,
              d
            );
          for (S in u)
            d = u[S], g = a[S], !u.hasOwnProperty(S) || d === g || d === void 0 && g === void 0 || kf(
              l,
              t,
              S,
              d,
              u,
              g
            );
          return;
        }
    }
    for (var m in a)
      d = a[m], a.hasOwnProperty(m) && d != null && !u.hasOwnProperty(m) && fl(l, t, m, null, u, d);
    for (b in u)
      d = u[b], g = a[b], !u.hasOwnProperty(b) || d === g || d == null && g == null || fl(l, t, b, d, u, g);
  }
  function od(l) {
    switch (l) {
      case "css":
      case "script":
      case "font":
      case "img":
      case "image":
      case "input":
      case "link":
        return !0;
      default:
        return !1;
    }
  }
  function rr() {
    if (typeof performance.getEntriesByType == "function") {
      for (var l = 0, t = 0, a = performance.getEntriesByType("resource"), u = 0; u < a.length; u++) {
        var e = a[u], n = e.transferSize, i = e.initiatorType, c = e.duration;
        if (n && c && od(i)) {
          for (i = 0, c = e.responseEnd, u += 1; u < a.length; u++) {
            var f = a[u], h = f.startTime;
            if (h > c) break;
            var S = f.transferSize, b = f.initiatorType;
            S && od(b) && (f = f.responseEnd, i += S * (f < c ? 1 : (c - h) / (f - h)));
          }
          if (--u, t += 8 * (n + i) / (e.duration / 1e3), l++, 10 < l) break;
        }
      }
      if (0 < l) return t / l / 1e6;
    }
    return navigator.connection && (l = navigator.connection.downlink, typeof l == "number") ? l : 5;
  }
  var If = null, Pf = null;
  function We(l) {
    return l.nodeType === 9 ? l : l.ownerDocument;
  }
  function vd(l) {
    switch (l) {
      case "http://www.w3.org/2000/svg":
        return 1;
      case "http://www.w3.org/1998/Math/MathML":
        return 2;
      default:
        return 0;
    }
  }
  function dd(l, t) {
    if (l === 0)
      switch (t) {
        case "svg":
          return 1;
        case "math":
          return 2;
        default:
          return 0;
      }
    return l === 1 && t === "foreignObject" ? 0 : l;
  }
  function md(l, t, a, u) {
    return a = We(
      a
    ).createElement(l), a[ql] = u, a[kl] = t, Vl(a, l, t), Cl(a), a;
  }
  function ls(l, t) {
    return l === "textarea" || l === "noscript" || typeof t.children == "string" || typeof t.children == "number" || typeof t.children == "bigint" || typeof t.dangerouslySetInnerHTML == "object" && t.dangerouslySetInnerHTML !== null && t.dangerouslySetInnerHTML.__html != null;
  }
  var ts = null;
  function yr() {
    var l = window.event;
    return l && l.type === "popstate" ? l === ts ? !1 : (ts = l, !0) : (ts = null, !1);
  }
  var as = typeof setTimeout == "function" ? setTimeout : void 0, gr = typeof clearTimeout == "function" ? clearTimeout : void 0, hd = typeof Promise == "function" ? Promise : void 0, rd = typeof requestAnimationFrame == "function" ? requestAnimationFrame : as, Sr = typeof queueMicrotask == "function" ? queueMicrotask : typeof hd < "u" ? function(l) {
    return hd.resolve(null).then(l).catch(Tr);
  } : as;
  function Tr(l) {
    setTimeout(function() {
      throw l;
    });
  }
  function Qa(l) {
    return l === "head";
  }
  function yd(l, t) {
    var a = t, u = 0;
    do {
      var e = a.nextSibling;
      if (l.removeChild(a), e && e.nodeType === 8)
        if (a = e.data, a === "/$" || a === "/&") {
          if (u === 0) {
            l.removeChild(e), se(t);
            return;
          }
          u--;
        } else if (a === "$" || a === "$?" || a === "$~" || a === "$!" || a === "&")
          u++;
        else if (a === "html")
          os(
            l.ownerDocument.documentElement
          );
        else if (a === "head") {
          a = l.ownerDocument.head, os(a);
          for (var n = a.firstChild; n; ) {
            var i = n.nextSibling, c = n.nodeName;
            n[he] || c === "SCRIPT" || c === "STYLE" || c === "LINK" && n.rel.toLowerCase() === "stylesheet" || a.removeChild(n), n = i;
          }
        } else
          a === "body" && os(l.ownerDocument.body);
      a = e;
    } while (a);
    se(t);
  }
  function gd(l, t) {
    var a = l;
    l = 0;
    do {
      var u = a.nextSibling;
      if (a.nodeType === 1 ? t ? (a._stashedDisplay = a.style.display, a.style.display = "none") : (a.style.display = a._stashedDisplay || "", a.getAttribute("style") === "" && a.removeAttribute("style")) : a.nodeType === 3 && (t ? (a._stashedText = a.nodeValue, a.nodeValue = "") : a.nodeValue = a._stashedText || ""), u && u.nodeType === 8)
        if (a = u.data, a === "/$") {
          if (l === 0) break;
          l--;
        } else
          a !== "$" && a !== "$?" && a !== "$~" && a !== "$!" || l++;
      a = u;
    } while (a);
  }
  function Sd(l, t, a) {
    if (t = CSS.escape(t) !== t ? "r-" + btoa(t).replace(/=/g, "") : t, l.style.viewTransitionName = t, a != null && (l.style.viewTransitionClass = a), a = getComputedStyle(l), a.display === "inline") {
      if (t = l.getClientRects(), t.length === 1) var u = 1;
      else
        for (var e = u = 0; e < t.length; e++) {
          var n = t[e];
          0 < n.width && 0 < n.height && u++;
        }
      u === 1 && (l = l.style, l.display = t.length === 1 ? "inline-block" : "block", l.marginTop = "-" + a.paddingTop, l.marginBottom = "-" + a.paddingBottom);
    }
  }
  function Td(l, t) {
    l = l.style, t = t.style;
    var a = t != null ? t.hasOwnProperty("viewTransitionName") ? t.viewTransitionName : t.hasOwnProperty("view-transition-name") ? t["view-transition-name"] : null : null;
    l.viewTransitionName = a == null || typeof a == "boolean" ? "" : ("" + a).trim(), a = t != null ? t.hasOwnProperty("viewTransitionClass") ? t.viewTransitionClass : t.hasOwnProperty("view-transition-class") ? t["view-transition-class"] : null : null, l.viewTransitionClass = a == null || typeof a == "boolean" ? "" : ("" + a).trim(), l.display === "inline-block" && (t == null ? l.display = l.margin = "" : (a = t.display, l.display = a == null || typeof a == "boolean" ? "" : a, a = t.margin, a != null ? l.margin = a : (a = t.hasOwnProperty("marginTop") ? t.marginTop : t["margin-top"], l.marginTop = a == null || typeof a == "boolean" ? "" : a, t = t.hasOwnProperty("marginBottom") ? t.marginBottom : t["margin-bottom"], l.marginBottom = t == null || typeof t == "boolean" ? "" : t)));
  }
  function Er(l, t, a) {
    return a = a.ownerDocument.defaultView, {
      rect: l,
      abs: t.position === "absolute" || t.position === "fixed",
      clip: t.clipPath !== "none" || t.overflow !== "visible" || t.filter !== "none" || t.mask !== "none" || t.mask !== "none" || t.borderRadius !== "0px",
      view: 0 <= l.bottom && 0 <= l.right && l.top <= a.innerHeight && l.left <= a.innerWidth
    };
  }
  function us(l) {
    var t = l.getBoundingClientRect(), a = getComputedStyle(l);
    return Er(t, a, l);
  }
  function br(l) {
    return l.documentElement.clientHeight;
  }
  function zr(l) {
    this.addEventListener("load", l), this.addEventListener("error", l);
  }
  function Nr(l, t, a, u, e, n, i, c, f) {
    var h = t.nodeType === 9 ? t : t.ownerDocument;
    try {
      var S = h.startViewTransition({
        update: function() {
          var d = h.defaultView, g = d.navigation && d.navigation.transition, _ = h.fonts.status;
          u();
          var D = [];
          if (_ === "loaded" && (br(h), h.fonts.status === "loading" && D.push(h.fonts.ready)), _ = D.length, l !== null)
            for (var Z = l.suspenseyImages, m = 0, s = 0; s < Z.length; s++) {
              var y = Z[s];
              if (!y.complete) {
                var T = y.getBoundingClientRect();
                if (0 < T.bottom && 0 < T.right && T.top < d.innerHeight && T.left < d.innerWidth) {
                  if (m += Xd(y), m > Mi) {
                    D.length = _;
                    break;
                  }
                  y = new Promise(
                    zr.bind(y)
                  ), D.push(y);
                }
              }
            }
          if (0 < D.length)
            return d = Promise.race([
              Promise.all(D),
              new Promise(function(A) {
                return setTimeout(A, 500);
              })
            ]).then(e, e), (g ? Promise.allSettled([g.finished, d]) : d).then(n, n);
          if (e(), g)
            return g.finished.then(
              n,
              n
            );
          n();
        },
        types: a
      });
      h.__reactViewTransition = S;
      var b = [];
      return S.ready.then(
        function() {
          for (var d = h.documentElement.getAnimations({
            subtree: !0
          }), g = 0; g < d.length; g++) {
            var _ = d[g], D = _.effect, Z = D.pseudoElement;
            if (Z != null && Z.startsWith("::view-transition")) {
              b.push(_), _ = D.getKeyframes();
              for (var m = Z = void 0, s = !0, y = 0; y < _.length; y++) {
                var T = _[y], A = T.width;
                if (Z === void 0) Z = A;
                else if (Z !== A) {
                  s = !1;
                  break;
                }
                if (A = T.height, m === void 0) m = A;
                else if (m !== A) {
                  s = !1;
                  break;
                }
                delete T.width, delete T.height, T.transform === "none" && delete T.transform;
              }
              s && Z !== void 0 && m !== void 0 && (D.setKeyframes(_), s = getComputedStyle(
                D.target,
                D.pseudoElement
              ), s.width !== Z || s.height !== m) && (s = _[0], s.width = Z, s.height = m, s = _[_.length - 1], s.width = Z, s.height = m, D.setKeyframes(_));
            }
          }
          i();
        },
        function(d) {
          h.__reactViewTransition === S && (h.__reactViewTransition = null);
          try {
            if (typeof d == "object" && d !== null)
              switch (d.name) {
                case "InvalidStateError":
                  (d.message === "View transition was skipped because document visibility state is hidden." || d.message === "Skipping view transition because document visibility state has become hidden." || d.message === "Skipping view transition because viewport size changed." || d.message === "Transition was aborted because of invalid state") && (d = null);
              }
            d !== null && f(d);
          } finally {
            u(), e(), i();
          }
        }
      ), S.finished.finally(function() {
        for (var d = 0; d < b.length; d++)
          b[d].cancel();
        h.__reactViewTransition === S && (h.__reactViewTransition = null), c();
      }), S;
    } catch {
      return u(), e(), i(), null;
    }
  }
  function Su(l, t) {
    this._scope = document.documentElement, this._selector = "::view-transition-" + l + "(" + t + ")";
  }
  Su.prototype.animate = function(l, t) {
    return t = typeof t == "number" ? { duration: t } : K({}, t), t.pseudoElement = this._selector, this._scope.animate(l, t);
  }, Su.prototype.getAnimations = function() {
    for (var l = this._scope, t = this._selector, a = l.getAnimations({ subtree: !0 }), u = [], e = 0; e < a.length; e++) {
      var n = a[e].effect;
      n !== null && n.target === l && n.pseudoElement === t && u.push(a[e]);
    }
    return u;
  }, Su.prototype.getComputedStyle = function() {
    return getComputedStyle(this._scope, this._selector);
  };
  function Ed(l) {
    return {
      name: l,
      group: new Su("group", l),
      imagePair: new Su("image-pair", l),
      old: new Su("old", l),
      new: new Su("new", l)
    };
  }
  function St(l) {
    this._fragmentFiber = l, this._observers = this._eventListeners = null;
  }
  St.prototype.addEventListener = function(l, t, a) {
    var u = null, e = null;
    if (!(a != null && typeof a != "boolean" && (u = a.signal || null, u !== null && u.aborted))) {
      this._eventListeners === null && (this._eventListeners = []);
      var n = this._eventListeners;
      if (zd(n, l, t, a) === -1) {
        var i = this, c = t;
        a != null && typeof a != "boolean" && a.once === !0 && (c = function(f) {
          i.removeEventListener(
            l,
            t,
            a
          ), typeof t == "function" ? t.call(this, f) : t.handleEvent(f);
        }), u !== null && (e = i.removeEventListener.bind(
          i,
          l,
          t,
          a
        ), u.addEventListener("abort", e, { once: !0 }), e = u.removeEventListener.bind(u, "abort", e)), u = ue(a), n.push({
          type: l,
          listener: t,
          optionsOrUseCapture: a,
          attachedListener: c,
          cleanup: e
        }), E(
          this._fragmentFiber.child,
          !1,
          _r,
          l,
          c,
          u
        );
      }
      this._eventListeners = n;
    }
  };
  function _r(l, t, a, u) {
    return dl(l).addEventListener(
      t,
      a,
      u
    ), !1;
  }
  St.prototype.removeEventListener = function(l, t, a) {
    var u = this._eventListeners;
    if (u !== null && (t = zd(
      u,
      l,
      t,
      a
    ), t !== -1)) {
      var e = u[t];
      a = e.attachedListener;
      var n = e.cleanup;
      e = ue(e.optionsOrUseCapture), E(
        this._fragmentFiber.child,
        !1,
        Or,
        l,
        a,
        e
      ), u.splice(t, 1), n !== null && n();
    }
  };
  function Or(l, t, a, u) {
    return dl(l).removeEventListener(
      t,
      a,
      u
    ), !1;
  }
  function ue(l) {
    return l != null && typeof l != "boolean" && (l.once === !0 || l.signal instanceof AbortSignal) ? { capture: l.capture, passive: l.passive } : l;
  }
  function bd(l) {
    return l == null ? "c=0" : typeof l == "boolean" ? "c=" + (l ? "1" : "0") : "c=" + (l.capture ? "1" : "0");
  }
  function zd(l, t, a, u) {
    if (l.length === 0) return -1;
    u = bd(u);
    for (var e = 0; e < l.length; e++) {
      var n = l[e];
      if (n.type === t && n.listener === a && bd(n.optionsOrUseCapture) === u)
        return e;
    }
    return -1;
  }
  St.prototype.dispatchEvent = function(l) {
    var t = H(
      this._fragmentFiber
    );
    if (t === null) return !0;
    t = dl(t);
    var a = this._eventListeners;
    if (a !== null && 0 < a.length || !l.bubbles) {
      var u = t.nodeType === 9 ? t.createComment("") : document.createTextNode("");
      if (a)
        for (var e = 0; e < a.length; e++) {
          var n = a[e];
          u.addEventListener(
            n.type,
            n.attachedListener,
            ue(n.optionsOrUseCapture)
          );
        }
      if (t.appendChild(u), l = u.dispatchEvent(l), a)
        for (e = 0; e < a.length; e++)
          n = a[e], u.removeEventListener(
            n.type,
            n.attachedListener,
            ue(n.optionsOrUseCapture)
          );
      return t.removeChild(u), l;
    }
    return t.dispatchEvent(l);
  }, St.prototype.focus = function(l) {
    E(
      this._fragmentFiber.child,
      !0,
      Nd,
      l,
      void 0,
      void 0
    );
  };
  function Nd(l, t) {
    return l.tag === 6 ? !1 : (l = dl(l), qr(l, t));
  }
  St.prototype.focusLast = function(l) {
    var t = [];
    E(
      this._fragmentFiber.child,
      !0,
      es,
      t,
      void 0,
      void 0
    );
    for (var a = t.length - 1; 0 <= a && !Nd(t[a], l); a--) ;
  };
  function es(l, t) {
    return t.push(l), !1;
  }
  St.prototype.blur = function() {
    var l = H(
      this._fragmentFiber
    );
    l !== null && (l = dl(l), l = We(l).activeElement, l !== null && E(
      this._fragmentFiber.child,
      !1,
      Ar,
      l,
      void 0,
      void 0
    ));
  };
  function Ar(l, t) {
    return l.tag === 6 ? !1 : (l = dl(l), l === t || l.contains(t) ? (t.blur(), !0) : !1);
  }
  St.prototype.observeUsing = function(l) {
    this._observers === null && (this._observers = /* @__PURE__ */ new Set()), this._observers.add(l), E(
      this._fragmentFiber.child,
      !1,
      Dr,
      l,
      void 0,
      void 0
    );
  };
  function Dr(l, t) {
    return l.tag === 6 || (l = dl(l), t.observe(l)), !1;
  }
  St.prototype.unobserveUsing = function(l) {
    var t = this._observers;
    if (t !== null && t.has(l)) {
      t.delete(l), E(
        this._fragmentFiber.child,
        !1,
        Mr,
        l,
        void 0,
        void 0
      );
      for (var a = t = 0; a < qt.length; a++) {
        var u = qt[a];
        u.fragmentInstance === this && u.observer === l ? l.unobserve(u.instance) : qt[t++] = u;
      }
      qt.length = t;
    }
  };
  function Mr(l, t) {
    return l.tag === 6 || (l = dl(l), t.unobserve(l)), !1;
  }
  var qt = [], ns = !1;
  function jr(l, t, a) {
    qt.push({
      fragmentInstance: l,
      observer: t,
      instance: a
    }), ns || (ns = !0, Br(function() {
      ns = !1;
      var u = qt;
      qt = [];
      for (var e = 0; e < u.length; e++) {
        var n = u[e];
        n.observer.unobserve(n.instance);
      }
    }));
  }
  St.prototype.getClientRects = function() {
    var l = [];
    return E(
      this._fragmentFiber.child,
      !1,
      Ur,
      l,
      void 0,
      void 0
    ), l;
  };
  function Ur(l, t) {
    if (l.tag === 6) {
      l = l.stateNode;
      var a = l.ownerDocument.createRange();
      a.selectNodeContents(l), t.push.apply(t, a.getClientRects());
    } else
      l = dl(l), t.push.apply(t, l.getClientRects());
    return !1;
  }
  St.prototype.getRootNode = function(l) {
    var t = H(
      this._fragmentFiber
    );
    return t === null ? this : dl(t).getRootNode(l);
  }, St.prototype.compareDocumentPosition = function(l) {
    var t = H(
      this._fragmentFiber
    );
    if (t === null) return Node.DOCUMENT_POSITION_DISCONNECTED;
    var a = [];
    E(
      this._fragmentFiber.child,
      !1,
      es,
      a,
      void 0,
      void 0
    );
    var u = dl(t);
    if (a.length === 0) {
      if (a = u, jl(this._fragmentFiber)) {
        l: {
          for (t = this._fragmentFiber.return; t !== null; ) {
            if (t.tag === 4) {
              t = t.stateNode.containerInfo;
              break l;
            }
            if (t.tag === 3 || t.tag === 5 || t.tag === 27)
              break;
            t = t.return;
          }
          t = null;
        }
        t != null && (a = t);
      }
      t = this._fragmentFiber;
      var e = u = a.compareDocumentPosition(l);
      return a === l ? e = Node.DOCUMENT_POSITION_CONTAINS : u & Node.DOCUMENT_POSITION_CONTAINED_BY && (a = et(t)[1], a === null ? e = Node.DOCUMENT_POSITION_PRECEDING : (l = dl(a).compareDocumentPosition(
        l
      ), e = l === 0 || l & Node.DOCUMENT_POSITION_FOLLOWING ? Node.DOCUMENT_POSITION_FOLLOWING : Node.DOCUMENT_POSITION_PRECEDING)), e |= Node.DOCUMENT_POSITION_IMPLEMENTATION_SPECIFIC;
    }
    t = dl(a[0]), e = dl(a[a.length - 1]);
    var n = jl(this._fragmentFiber) ? t.parentElement : u;
    if (n == null)
      return Node.DOCUMENT_POSITION_DISCONNECTED;
    u = n.compareDocumentPosition(t) & Node.DOCUMENT_POSITION_CONTAINED_BY, n = n.compareDocumentPosition(e) & Node.DOCUMENT_POSITION_CONTAINED_BY;
    var i = t.compareDocumentPosition(l), c = e.compareDocumentPosition(l), f = i & Node.DOCUMENT_POSITION_CONTAINED_BY || c & Node.DOCUMENT_POSITION_CONTAINED_BY;
    return c = u && n && i & Node.DOCUMENT_POSITION_FOLLOWING && c & Node.DOCUMENT_POSITION_PRECEDING, t = u && t === l || n && e === l || f || c ? Node.DOCUMENT_POSITION_CONTAINED_BY : !u && t === l || !n && e === l ? Node.DOCUMENT_POSITION_IMPLEMENTATION_SPECIFIC : i, t & Node.DOCUMENT_POSITION_DISCONNECTED || t & Node.DOCUMENT_POSITION_IMPLEMENTATION_SPECIFIC || Cr(
      t,
      this._fragmentFiber,
      a[0],
      a[a.length - 1],
      l
    ) ? t : Node.DOCUMENT_POSITION_IMPLEMENTATION_SPECIFIC;
  };
  function Cr(l, t, a, u, e) {
    var n = ka(e);
    if (l & Node.DOCUMENT_POSITION_CONTAINED_BY) {
      if (a = !!n)
        l: {
          for (; n !== null; ) {
            if (n.tag === 7 && (n === t || n.alternate === t)) {
              a = !0;
              break l;
            }
            n = n.return;
          }
          a = !1;
        }
      return a;
    }
    if (l & Node.DOCUMENT_POSITION_CONTAINS) {
      if (n === null)
        return n = e.ownerDocument, e === n || e === n.documentElement || e === n.body;
      l: {
        for (n = t, t = H(t); n !== null; ) {
          if (!(n.tag !== 5 && n.tag !== 3 && n.tag !== 27 || n !== t && n.alternate !== t)) {
            n = !0;
            break l;
          }
          n = n.return;
        }
        n = !1;
      }
      return n;
    }
    return l & Node.DOCUMENT_POSITION_PRECEDING ? ((t = !!n) && !(t = n === a) && (t = it(
      a,
      n,
      Gt
    ), t === null ? t = !1 : (E(
      t,
      !0,
      wa,
      n,
      a
    ), n = Tt, Tt = null, t = n !== null)), t) : l & Node.DOCUMENT_POSITION_FOLLOWING ? ((t = !!n) && !(t = n === u) && (t = it(
      u,
      n,
      Gt
    ), t === null ? t = !1 : (E(
      t,
      !0,
      Bt,
      n,
      u
    ), n = Tt, nt = Tt = null, t = n !== null)), t) : !1;
  }
  function _d(l, t) {
    var a = l.ownerDocument.createRange();
    a.selectNodeContents(l), l = a.getBoundingClientRect(), window.scrollTo(
      window.scrollX + l.left,
      t ? window.scrollY + l.top : window.scrollY + l.bottom - window.innerHeight
    );
  }
  St.prototype.scrollIntoView = function(l) {
    if (typeof l == "object") throw Error(r(566));
    var t = [];
    E(
      this._fragmentFiber.child,
      !1,
      es,
      t,
      void 0,
      void 0
    );
    var a = l !== !1;
    if (t.length === 0) {
      var u = et(
        this._fragmentFiber
      );
      if (u = a ? u[1] || u[0] || H(this._fragmentFiber) : u[0] || u[1], u === null) return;
      if (u.tag === 6) {
        l = dl(u), _d(l, a);
        return;
      }
      if (u = dl(u), u.nodeType !== 9) {
        if (u.nodeType === 11) {
          a = "host" in u ? u.host : null, a !== null && a.scrollIntoView(l);
          return;
        }
        u.scrollIntoView(l);
      }
    }
    for (u = a ? t.length - 1 : 0; u !== (a ? -1 : t.length); ) {
      var e = t[u];
      e.tag === 6 ? (e = dl(e), _d(e, a)) : dl(e).scrollIntoView(l), u += a ? -1 : 1;
    }
  };
  function Rr(l, t) {
    return l = dl(l), Od(l, t), !1;
  }
  function Od(l, t) {
    l.reactFragments == null && (l.reactFragments = /* @__PURE__ */ new Set()), l.reactFragments.add(t);
  }
  function Ad(l, t) {
    var a = t._eventListeners;
    if (a !== null)
      for (var u = 0; u < a.length; u++) {
        var e = a[u];
        l.addEventListener(
          e.type,
          e.attachedListener,
          ue(e.optionsOrUseCapture)
        );
      }
    l.nodeType !== 3 && (a = t._observers, a !== null && a.forEach(function(n) {
      for (var i = 0, c = 0; c < qt.length; c++) {
        var f = qt[c];
        (f.fragmentInstance !== t || f.observer !== n || f.instance !== l) && (qt[i++] = f);
      }
      qt.length = i, n.observe(l);
    }), Od(l, t));
  }
  function xr(l, t) {
    var a = t._eventListeners;
    if (a !== null)
      for (var u = 0; u < a.length; u++) {
        var e = a[u];
        l.removeEventListener(
          e.type,
          e.attachedListener,
          ue(e.optionsOrUseCapture)
        );
      }
    l.nodeType !== 3 && (a = t._observers, a !== null && a.forEach(function(n) {
      typeof n.rootMargin == "string" ? jr(
        t,
        n,
        l
      ) : n.unobserve(l);
    }), l.reactFragments != null && l.reactFragments.delete(t));
  }
  function is(l) {
    var t = l.firstChild;
    for (t && t.nodeType === 10 && (t = t.nextSibling); t; ) {
      var a = t;
      switch (t = t.nextSibling, a.nodeName) {
        case "HTML":
        case "HEAD":
        case "BODY":
          is(a), yn(a);
          continue;
        case "SCRIPT":
        case "STYLE":
          continue;
        case "LINK":
          if (a.rel.toLowerCase() === "stylesheet") continue;
      }
      l.removeChild(a);
    }
  }
  function Hr(l, t, a, u) {
    for (; l.nodeType === 1; ) {
      var e = a;
      if (l.nodeName.toLowerCase() !== t.toLowerCase()) {
        if (!u && (l.nodeName !== "INPUT" || l.type !== "hidden"))
          break;
      } else if (u) {
        if (!l[he])
          switch (t) {
            case "meta":
              if (!l.hasAttribute("itemprop")) break;
              return l;
            case "link":
              if (n = l.getAttribute("rel"), n === "stylesheet" && l.hasAttribute("data-precedence"))
                break;
              if (n !== e.rel || l.getAttribute("href") !== (e.href == null || e.href === "" ? null : e.href) || l.getAttribute("crossorigin") !== (e.crossOrigin == null ? null : e.crossOrigin) || l.getAttribute("title") !== (e.title == null ? null : e.title))
                break;
              return l;
            case "style":
              if (l.hasAttribute("data-precedence")) break;
              return l;
            case "script":
              if (n = l.getAttribute("src"), (n !== (e.src == null ? null : e.src) || l.getAttribute("type") !== (e.type == null ? null : e.type) || l.getAttribute("crossorigin") !== (e.crossOrigin == null ? null : e.crossOrigin)) && n && l.hasAttribute("async") && !l.hasAttribute("itemprop"))
                break;
              return l;
            default:
              return l;
          }
      } else if (t === "input" && l.type === "hidden") {
        var n = e.name == null ? null : "" + e.name;
        if (e.type === "hidden" && l.getAttribute("name") === n)
          return l;
      } else return l;
      if (l = jt(l.nextSibling), l === null) break;
    }
    return null;
  }
  function pr(l, t, a) {
    if (t === "") return null;
    for (; l.nodeType !== 3; )
      if ((l.nodeType !== 1 || l.nodeName !== "INPUT" || l.type !== "hidden") && !a || (l = jt(l.nextSibling), l === null)) return null;
    return l;
  }
  function Dd(l, t) {
    for (; l.nodeType !== 8; )
      if ((l.nodeType !== 1 || l.nodeName !== "INPUT" || l.type !== "hidden") && !t || (l = jt(l.nextSibling), l === null)) return null;
    return l;
  }
  function cs(l) {
    return l.data === "$?" || l.data === "$~";
  }
  function fs(l) {
    return l.data === "$!" || l.data === "$?" && l.ownerDocument.readyState !== "loading";
  }
  function Yr(l, t) {
    var a = l.ownerDocument;
    if (l.data === "$~") l._reactRetry = t;
    else if (l.data !== "$?" || a.readyState !== "loading")
      t();
    else {
      var u = function() {
        t(), a.removeEventListener("DOMContentLoaded", u);
      };
      a.addEventListener("DOMContentLoaded", u), l._reactRetry = u;
    }
  }
  function jt(l) {
    for (; l != null; l = l.nextSibling) {
      var t = l.nodeType;
      if (t === 1 || t === 3) break;
      if (t === 8) {
        if (t = l.data, t === "$" || t === "$!" || t === "$?" || t === "$~" || t === "&" || t === "F!" || t === "F")
          break;
        if (t === "/$" || t === "/&") return null;
      }
    }
    return l;
  }
  var ss = null;
  function Md(l) {
    l = l.nextSibling;
    for (var t = 0; l; ) {
      if (l.nodeType === 8) {
        var a = l.data;
        if (a === "/$" || a === "/&") {
          if (t === 0)
            return jt(l.nextSibling);
          t--;
        } else
          a !== "$" && a !== "$!" && a !== "$?" && a !== "$~" && a !== "&" || t++;
      }
      l = l.nextSibling;
    }
    return null;
  }
  function jd(l) {
    l = l.previousSibling;
    for (var t = 0; l; ) {
      if (l.nodeType === 8) {
        var a = l.data;
        if (a === "$" || a === "$!" || a === "$?" || a === "$~" || a === "&") {
          if (t === 0) return l;
          t--;
        } else a !== "/$" && a !== "/&" || t++;
      }
      l = l.previousSibling;
    }
    return null;
  }
  function qr(l, t) {
    function a() {
      u = !0;
    }
    if (l.ownerDocument.activeElement === l) return !0;
    var u = !1;
    try {
      l.ownerDocument.addEventListener("focus", a, !0), (l.focus || HTMLElement.prototype.focus).call(l, t);
    } finally {
      l.ownerDocument.removeEventListener("focus", a, !0);
    }
    return u;
  }
  function Br(l) {
    rd(function() {
      rd(function(t) {
        return l(t);
      });
    });
  }
  function Ud(l, t, a) {
    switch (t = We(a), l) {
      case "html":
        if (l = t.documentElement, !l) throw Error(r(452));
        return l;
      case "head":
        if (l = t.head, !l) throw Error(r(453));
        return l;
      case "body":
        if (l = t.body, !l) throw Error(r(454));
        return l;
      default:
        throw Error(r(451));
    }
  }
  function Cd(l, t, a) {
    for (var u in a) {
      var e = a[u];
      a.hasOwnProperty(u) && e != null && fl(l, t, u, null, mr, e);
    }
    a.dangerouslySetInnerHTML != null && (l.textContent = ""), l.onclick === Zt && (l.onclick = null), yn(l);
  }
  function os(l) {
    for (var t = l.attributes; t.length; )
      l.removeAttributeNode(t[0]);
    yn(l);
  }
  var Ut = /* @__PURE__ */ new Map(), Rd = /* @__PURE__ */ new Set();
  function ke(l) {
    if (typeof l.getRootNode == "function") {
      var t = l.getRootNode();
      if (t.nodeType === 9 || t.nodeType === 11) return t;
    }
    return l.nodeType === 9 ? l : l.ownerDocument;
  }
  var ya = G.d;
  G.d = {
    f: Gr,
    r: Xr,
    D: Qr,
    C: Vr,
    L: Zr,
    m: Lr,
    X: Jr,
    S: Kr,
    M: wr
  };
  function Gr() {
    var l = ya.f(), t = Ti();
    return l || t;
  }
  function Xr(l) {
    var t = zu(l);
    t !== null && t.tag === 5 && t.type === "form" ? H0(t) : ya.r(l);
  }
  var ee = typeof document > "u" ? null : document;
  function xd(l, t, a) {
    var u = ee;
    if (u && typeof t == "string" && t) {
      var e = zt(t);
      e = 'link[rel="' + l + '"][href="' + e + '"]', typeof a == "string" && (e += '[crossorigin="' + a + '"]'), Rd.has(e) || (Rd.add(e), l = { rel: l, crossOrigin: a, href: t }, u.querySelector(e) === null && (t = u.createElement("link"), Vl(t, "link", l), Cl(t), u.head.appendChild(t)));
    }
  }
  function Qr(l) {
    ya.D(l), xd("dns-prefetch", l, null);
  }
  function Vr(l, t) {
    ya.C(l, t), xd("preconnect", l, t);
  }
  function Zr(l, t, a) {
    ya.L(l, t, a);
    var u = ee;
    if (u && l && t) {
      var e = 'link[rel="preload"][as="' + zt(t) + '"]';
      t === "image" && a && a.imageSrcSet ? (e += '[imagesrcset="' + zt(
        a.imageSrcSet
      ) + '"]', typeof a.imageSizes == "string" && (e += '[imagesizes="' + zt(
        a.imageSizes
      ) + '"]')) : e += '[href="' + zt(l) + '"]';
      var n = e;
      switch (t) {
        case "style":
          n = ne(l);
          break;
        case "script":
          n = ie(l);
      }
      if (!(Ut.has(n) || (l = K(
        {
          rel: "preload",
          href: t === "image" && a && a.imageSrcSet ? void 0 : l,
          as: t
        },
        a
      ), Ut.set(n, l), u.querySelector(e) !== null || t === "style" && u.querySelector(Ie(n)) || t === "script" && u.querySelector(Pe(n))))) {
        var i = u.createElement("link");
        Vl(i, "link", l), t === "style" && (i[rn] = !0, i.onload = i.onerror = function() {
          Ks(i);
        }), Cl(i), u.head.appendChild(i);
      }
    }
  }
  function Lr(l, t) {
    ya.m(l, t);
    var a = ee;
    if (a && l) {
      var u = t && typeof t.as == "string" ? t.as : "script", e = 'link[rel="modulepreload"][as="' + zt(u) + '"][href="' + zt(l) + '"]', n = e;
      switch (u) {
        case "audioworklet":
        case "paintworklet":
        case "serviceworker":
        case "sharedworker":
        case "worker":
        case "script":
          n = ie(l);
      }
      if (!Ut.has(n) && (l = K({ rel: "modulepreload", href: l }, t), Ut.set(n, l), a.querySelector(e) === null)) {
        switch (u) {
          case "audioworklet":
          case "paintworklet":
          case "serviceworker":
          case "sharedworker":
          case "worker":
          case "script":
            if (a.querySelector(Pe(n)))
              return;
        }
        u = a.createElement("link"), Vl(u, "link", l), Cl(u), a.head.appendChild(u);
      }
    }
  }
  function Kr(l, t, a) {
    ya.S(l, t, a);
    var u = ee;
    if (u && l) {
      var e = Nu(u).hoistableStyles, n = ne(l);
      t = t || "default";
      var i = e.get(n);
      if (!i) {
        var c = { loading: 0, preload: null };
        if (i = u.querySelector(
          Ie(n)
        ))
          c.loading = 5;
        else {
          l = K(
            { rel: "stylesheet", href: l, "data-precedence": t },
            a
          ), (a = Ut.get(n)) && vs(l, a);
          var f = i = u.createElement("link");
          Cl(f), Vl(f, "link", l), f._p = new Promise(function(h, S) {
            f.onload = h, f.onerror = S;
          }), f.addEventListener("load", function() {
            c.loading |= 1;
          }), f.addEventListener("error", function() {
            c.loading |= 2;
          }), c.loading |= 4, Ai(i, t, u);
        }
        i = {
          type: "stylesheet",
          instance: i,
          count: 1,
          state: c
        }, e.set(n, i);
      }
    }
  }
  function Jr(l, t) {
    ya.X(l, t);
    var a = ee;
    if (a && l) {
      var u = Nu(a).hoistableScripts, e = ie(l), n = u.get(e);
      n || (n = a.querySelector(Pe(e)), n || (l = K({ src: l, async: !0 }, t), (t = Ut.get(e)) && ds(l, t), n = a.createElement("script"), Cl(n), Vl(n, "link", l), a.head.appendChild(n)), n = {
        type: "script",
        instance: n,
        count: 1,
        state: null
      }, u.set(e, n));
    }
  }
  function wr(l, t) {
    ya.M(l, t);
    var a = ee;
    if (a && l) {
      var u = Nu(a).hoistableScripts, e = ie(l), n = u.get(e);
      n || (n = a.querySelector(Pe(e)), n || (l = K({ src: l, async: !0, type: "module" }, t), (t = Ut.get(e)) && ds(l, t), n = a.createElement("script"), Cl(n), Vl(n, "link", l), a.head.appendChild(n)), n = {
        type: "script",
        instance: n,
        count: 1,
        state: null
      }, u.set(e, n));
    }
  }
  function Hd(l, t, a, u) {
    var e = (e = Sa.current) ? ke(e) : null;
    if (!e) throw Error(r(446));
    switch (l) {
      case "meta":
      case "title":
        return null;
      case "style":
        return typeof a.precedence == "string" && typeof a.href == "string" ? (a = ne(a.href), t = Nu(
          e
        ).hoistableStyles, u = t.get(a), u || (u = {
          type: "style",
          instance: null,
          count: 0,
          state: null
        }, t.set(a, u)), u) : { type: "void", instance: null, count: 0, state: null };
      case "link":
        if (a.rel === "stylesheet" && typeof a.href == "string" && typeof a.precedence == "string") {
          l = ne(a.href);
          var n = Nu(
            e
          ).hoistableStyles, i = n.get(l);
          if (i || (e = e.ownerDocument || e, i = {
            type: "stylesheet",
            instance: null,
            count: 0,
            state: { loading: 0, preload: null }
          }, n.set(l, i), (n = e.querySelector(
            Ie(l)
          )) ? n._p || (i.instance = n, i.state.loading = 5) : (n = Ut.get(l), n || (n = {
            rel: "preload",
            as: "style",
            href: a.href,
            crossOrigin: a.crossOrigin,
            integrity: a.integrity,
            media: a.media,
            hrefLang: a.hrefLang,
            referrerPolicy: a.referrerPolicy
          }, Ut.set(l, n)), Fr(
            e,
            l,
            n,
            i.state
          ))), t && u === null)
            throw Error(r(528, ""));
          return i;
        }
        if (t && u !== null)
          throw Error(r(529, ""));
        return null;
      case "script":
        return t = a.async, a = a.src, typeof a == "string" && t && typeof t != "function" && typeof t != "symbol" ? (a = ie(a), t = Nu(
          e
        ).hoistableScripts, u = t.get(a), u || (u = {
          type: "script",
          instance: null,
          count: 0,
          state: null
        }, t.set(a, u)), u) : { type: "void", instance: null, count: 0, state: null };
      default:
        throw Error(r(444, l));
    }
  }
  function ne(l) {
    return 'href="' + zt(l) + '"';
  }
  function Ie(l) {
    return 'link[rel="stylesheet"][' + l + "]";
  }
  function pd(l) {
    return K({}, l, {
      "data-precedence": l.precedence,
      precedence: null
    });
  }
  function Fr(l, t, a, u) {
    if (t = l.querySelector(
      'link[rel="preload"][as="style"][' + t + "]"
    )) {
      if (t[rn] !== !0) {
        u.loading = 1;
        return;
      }
    } else
      t = l.createElement("link"), t[rn] = !0, t.onload = t.onerror = Ks.bind(null, t), Vl(t, "link", a), Cl(t), l.head.appendChild(t);
    u.preload = t, t.addEventListener("load", function() {
      return u.loading |= 1;
    }), t.addEventListener("error", function() {
      return u.loading |= 2;
    });
  }
  function ie(l) {
    return '[src="' + zt(l) + '"]';
  }
  function Pe(l) {
    return "script[async]" + l;
  }
  function Yd(l, t, a) {
    if (t.count++, t.instance === null)
      switch (t.type) {
        case "style":
          var u = l.querySelector(
            'style[data-href~="' + zt(a.href) + '"]'
          );
          if (u)
            return t.instance = u, Cl(u), u;
          var e = K({}, a, {
            "data-href": a.href,
            "data-precedence": a.precedence,
            href: null,
            precedence: null
          });
          return u = (l.ownerDocument || l).createElement(
            "style"
          ), Cl(u), Vl(u, "style", e), Ai(u, a.precedence, l), t.instance = u;
        case "stylesheet":
          e = ne(a.href);
          var n = l.querySelector(
            Ie(e)
          );
          if (n)
            return t.state.loading |= 4, t.instance = n, Cl(n), n;
          u = pd(a), (e = Ut.get(e)) && vs(u, e), n = (l.ownerDocument || l).createElement("link"), Cl(n);
          var i = n;
          return i._p = new Promise(function(c, f) {
            i.onload = c, i.onerror = f;
          }), Vl(n, "link", u), t.state.loading |= 4, Ai(n, a.precedence, l), t.instance = n;
        case "script":
          return n = ie(a.src), (e = l.querySelector(
            Pe(n)
          )) ? (t.instance = e, Cl(e), e) : (u = a, (e = Ut.get(n)) && (u = K({}, a), ds(u, e)), l = l.ownerDocument || l, e = l.createElement("script"), Cl(e), Vl(e, "link", u), l.head.appendChild(e), t.instance = e);
        case "void":
          return null;
        default:
          throw Error(r(443, t.type));
      }
    else
      t.type === "stylesheet" && (t.state.loading & 4) === 0 && (u = t.instance, t.state.loading |= 4, Ai(u, a.precedence, l));
    return t.instance;
  }
  function Ai(l, t, a) {
    for (var u = a.querySelectorAll(
      'link[rel="stylesheet"][data-precedence],style[data-precedence]'
    ), e = u.length ? u[u.length - 1] : null, n = e, i = 0; i < u.length; i++) {
      var c = u[i];
      if (c.dataset.precedence === t) n = c;
      else if (n !== e) break;
    }
    n ? n.parentNode.insertBefore(l, n.nextSibling) : (t = a.nodeType === 9 ? a.head : a, t.insertBefore(l, t.firstChild));
  }
  function vs(l, t) {
    l.crossOrigin == null && (l.crossOrigin = t.crossOrigin), l.referrerPolicy == null && (l.referrerPolicy = t.referrerPolicy), l.title == null && (l.title = t.title);
  }
  function ds(l, t) {
    l.crossOrigin == null && (l.crossOrigin = t.crossOrigin), l.referrerPolicy == null && (l.referrerPolicy = t.referrerPolicy), l.integrity == null && (l.integrity = t.integrity);
  }
  var Di = null;
  function qd(l, t, a) {
    if (Di === null) {
      var u = /* @__PURE__ */ new Map(), e = Di = /* @__PURE__ */ new Map();
      e.set(a, u);
    } else
      e = Di, u = e.get(a), u || (u = /* @__PURE__ */ new Map(), e.set(a, u));
    if (u.has(l)) return u;
    for (u.set(l, null), a = a.getElementsByTagName(l), e = 0; e < a.length; e++) {
      var n = a[e];
      if (!(n[he] || n[ql] || l === "link" && n.getAttribute("rel") === "stylesheet") && n.namespaceURI !== "http://www.w3.org/2000/svg") {
        var i = n.getAttribute(t) || "";
        i = l + i;
        var c = u.get(i);
        c ? c.push(n) : u.set(i, [n]);
      }
    }
    return u;
  }
  function ms(l, t, a) {
    l = l.ownerDocument || l, l.head.insertBefore(
      a,
      t === "title" ? l.querySelector("head > title") : null
    );
  }
  function $r(l, t, a) {
    if (a === 1 || t.itemProp != null) return !1;
    switch (l) {
      case "meta":
      case "title":
        return !0;
      case "style":
        if (typeof t.precedence != "string" || typeof t.href != "string" || t.href === "")
          break;
        return !0;
      case "link":
        if (typeof t.rel != "string" || typeof t.href != "string" || t.href === "" || t.onLoad || t.onError)
          break;
        switch (t.rel) {
          case "stylesheet":
            return l = t.disabled, typeof t.precedence == "string" && l == null;
          default:
            return !0;
        }
      case "script":
        if (t.async && typeof t.async != "function" && typeof t.async != "symbol" && !t.onLoad && !t.onError && t.src && typeof t.src == "string")
          return !0;
    }
    return !1;
  }
  function Bd(l, t) {
    return l === "img" && t.src != null && t.src !== "" && t.onLoad == null && t.loading !== "lazy";
  }
  function Gd(l) {
    return !(l.type === "stylesheet" && (l.state.loading & 3) === 0);
  }
  function Xd(l) {
    return (l.width || 100) * (l.height || 100) * (typeof devicePixelRatio == "number" ? devicePixelRatio : 1) * 0.25;
  }
  function Qd(l, t) {
    typeof t.decode == "function" && (l.imgCount++, t.complete || (l.imgBytes += Xd(t), l.suspenseyImages.push(t)), l = Ir.bind(l), t.decode().then(l, l));
  }
  function Wr(l, t, a, u) {
    if (a.type === "stylesheet" && (typeof u.media != "string" || matchMedia(u.media).matches !== !1) && (a.state.loading & 4) === 0) {
      if (a.instance === null) {
        var e = ne(u.href), n = t.querySelector(
          Ie(e)
        );
        if (n) {
          t = n._p, t !== null && typeof t == "object" && typeof t.then == "function" && (l.count++, l = ln.bind(l), t.then(l, l)), a.state.loading |= 4, a.instance = n, Cl(n);
          return;
        }
        n = t.ownerDocument || t, u = pd(u), (e = Ut.get(e)) && vs(u, e), n = n.createElement("link"), Cl(n);
        var i = n;
        i._p = new Promise(function(c, f) {
          i.onload = c, i.onerror = f;
        }), Vl(n, "link", u), a.instance = n;
      }
      l.stylesheets === null && (l.stylesheets = /* @__PURE__ */ new Map()), l.stylesheets.set(a, t), (t = a.state.preload) && (a.state.loading & 3) === 0 && (l.count++, a = ln.bind(l), t.addEventListener("load", a), t.addEventListener("error", a));
    }
  }
  var Mi = 0;
  function kr(l, t) {
    return l.stylesheets && l.count === 0 && Ui(l, l.stylesheets), 0 < l.count || 0 < l.imgCount ? function(a) {
      var u = setTimeout(function() {
        if (l.stylesheets && Ui(l, l.stylesheets), l.unsuspend) {
          var n = l.unsuspend;
          l.unsuspend = null, n();
        }
      }, 6e4 + t);
      0 < l.imgBytes && Mi === 0 && (Mi = 62500 * rr());
      var e = setTimeout(
        function() {
          if (l.waitingForImages = !1, l.count === 0 && (l.stylesheets && Ui(l, l.stylesheets), l.unsuspend)) {
            var n = l.unsuspend;
            l.unsuspend = null, n();
          }
        },
        (l.imgBytes > Mi ? 50 : 800) + t
      );
      return l.unsuspend = a, function() {
        l.unsuspend = null, clearTimeout(u), clearTimeout(e);
      };
    } : null;
  }
  function Vd(l) {
    if (l.count === 0 && (l.imgCount === 0 || !l.waitingForImages)) {
      if (l.stylesheets) Ui(l, l.stylesheets);
      else if (l.unsuspend) {
        var t = l.unsuspend;
        l.unsuspend = null, t();
      }
    }
  }
  function ln() {
    this.count--, Vd(this);
  }
  function Ir() {
    this.imgCount--, Vd(this);
  }
  var ji = null;
  function Ui(l, t) {
    l.stylesheets = null, l.unsuspend !== null && (l.count++, ji = /* @__PURE__ */ new Map(), t.forEach(Pr, l), ji = null, ln.call(l));
  }
  function Pr(l, t) {
    if (!(t.state.loading & 4)) {
      var a = ji.get(l);
      if (a) var u = a.get(null);
      else {
        a = /* @__PURE__ */ new Map(), ji.set(l, a);
        for (var e = l.querySelectorAll(
          "link[data-precedence],style[data-precedence]"
        ), n = 0; n < e.length; n++) {
          var i = e[n];
          (i.nodeName === "LINK" || i.getAttribute("media") !== "not all") && (a.set(i.dataset.precedence, i), u = i);
        }
        u && a.set(null, u);
      }
      e = t.instance, i = e.getAttribute("data-precedence"), n = a.get(i) || u, n === u && a.set(null, e), a.set(i, e), this.count++, u = ln.bind(this), e.addEventListener("load", u), e.addEventListener("error", u), n ? n.parentNode.insertBefore(e, n.nextSibling) : (l = l.nodeType === 9 ? l.head : l, l.insertBefore(e, l.firstChild)), t.state.loading |= 4;
    }
  }
  var ce = {
    $$typeof: Ul,
    Provider: null,
    Consumer: null,
    _currentValue: ta,
    _currentValue2: ta,
    _threadCount: 0
  };
  function ly(l, t, a, u, e, n, i, c, f) {
    this.tag = 1, this.containerInfo = l, this.pingCache = this.current = this.pendingChildren = null, this.timeoutHandle = -1, this.callbackNode = this.next = this.pendingContext = this.context = this.cancelPendingCommit = null, this.callbackPriority = 0, this.expirationTimes = Ji(-1), this.entangledLanes = this.shellSuspendCounter = this.errorRecoveryDisabledLanes = this.expiredLanes = this.warmLanes = this.pingedLanes = this.suspendedLanes = this.pendingLanes = 0, this.entanglements = Ji(0), this.hiddenUpdates = Ji(null), this.identifierPrefix = u, this.onUncaughtError = e, this.onCaughtError = n, this.onRecoverableError = i, this.pooledCache = null, this.pooledCacheLanes = 0, this.formState = f, this.transitionTypes = null, this.incompleteTransitions = /* @__PURE__ */ new Map();
  }
  function Zd(l, t, a, u, e, n, i, c, f, h, S, b) {
    return l = new ly(
      l,
      t,
      a,
      i,
      f,
      h,
      S,
      b,
      c
    ), t = 1, n === !0 && (t |= 24), n = Il(3, null, null, t), l.current = n, n.stateNode = l, t = Dc(), t.refCount++, l.pooledCache = t, t.refCount++, n.memoizedState = {
      element: u,
      isDehydrated: a,
      cache: t
    }, Cc(n), l;
  }
  function Ld(l) {
    return l ? (l = xu, l) : xu;
  }
  function Kd(l, t, a, u, e, n) {
    e = Ld(e), u.context === null ? u.context = e : u.pendingContext = e, u = ja(t), u.payload = { element: a }, n = n === void 0 ? null : n, n !== null && (u.callback = n), a = Ua(l, u, t), a !== null && (at(a, l, t), Ce(a, l, t));
  }
  function Jd(l, t) {
    if (l = l.memoizedState, l !== null && l.dehydrated !== null) {
      var a = l.retryLane;
      l.retryLane = a !== 0 && a < t ? a : t;
    }
  }
  function hs(l, t) {
    Jd(l, t), (l = l.alternate) && Jd(l, t);
  }
  function wd(l) {
    if (l.tag === 13 || l.tag === 31) {
      var t = tu(l, 67108864);
      t !== null && at(t, l, 67108864), hs(l, 67108864);
    }
  }
  function Fd(l) {
    if (l.tag === 13 || l.tag === 31) {
      var t = gt();
      t = wi(t);
      var a = tu(l, t);
      a !== null && at(a, l, t), hs(l, t);
    }
  }
  var fe = !0;
  function ty(l, t, a, u) {
    var e = M.T;
    M.T = null;
    var n = G.p;
    try {
      G.p = 2, rs(l, t, a, u);
    } finally {
      G.p = n, M.T = e;
    }
  }
  function ay(l, t, a, u) {
    var e = M.T;
    M.T = null;
    var n = G.p;
    try {
      G.p = 8, rs(l, t, a, u);
    } finally {
      G.p = n, M.T = e;
    }
  }
  function rs(l, t, a, u) {
    if (fe) {
      var e = ys(u);
      if (e === null)
        Wf(
          l,
          t,
          u,
          Ci,
          a
        ), Wd(l, u);
      else if (ey(
        e,
        l,
        t,
        a,
        u
      ))
        u.stopPropagation();
      else if (Wd(l, u), t & 4 && -1 < uy.indexOf(l)) {
        for (; e !== null; ) {
          var n = zu(e);
          if (n !== null)
            switch (n.tag) {
              case 3:
                if (n = n.stateNode, n.current.memoizedState.isDehydrated) {
                  var i = Wa(n.pendingLanes);
                  if (i !== 0) {
                    var c = n;
                    for (c.pendingLanes |= 2, c.entangledLanes |= 2; i; ) {
                      var f = 1 << 31 - ot(i);
                      c.entanglements[1] |= f, i &= ~f;
                    }
                    Pt(n), (ul & 6) === 0 && (yi = ft() + 500, we(0));
                  }
                }
                break;
              case 31:
              case 13:
                c = tu(n, 2), c !== null && at(c, n, 2), Ti(), hs(n, 2);
            }
          if (n = ys(u), n === null && Wf(
            l,
            t,
            u,
            Ci,
            a
          ), n === e) break;
          e = n;
        }
        e !== null && u.stopPropagation();
      } else
        Wf(
          l,
          t,
          u,
          null,
          a
        );
    }
  }
  function ys(l) {
    return l = lc(l), gs(l);
  }
  var Ci = null;
  function gs(l) {
    if (Ci = null, l = ka(l), l !== null) {
      var t = U(l);
      if (t === null) l = null;
      else {
        var a = t.tag;
        if (a === 13) {
          if (l = Al(t), l !== null) return l;
          l = null;
        } else if (a === 31) {
          if (l = $l(t), l !== null) return l;
          l = null;
        } else if (a === 3) {
          if (t.stateNode.current.memoizedState.isDehydrated)
            return t.tag === 3 ? t.stateNode.containerInfo : null;
          l = null;
        } else t !== l && (l = null);
      }
    }
    return Ci = l, null;
  }
  function $d(l) {
    switch (l) {
      case "beforetoggle":
      case "cancel":
      case "click":
      case "close":
      case "contextmenu":
      case "copy":
      case "cut":
      case "auxclick":
      case "dblclick":
      case "dragend":
      case "dragstart":
      case "drop":
      case "focusin":
      case "focusout":
      case "input":
      case "invalid":
      case "keydown":
      case "keypress":
      case "keyup":
      case "mousedown":
      case "mouseup":
      case "paste":
      case "pause":
      case "play":
      case "pointercancel":
      case "pointerdown":
      case "pointerup":
      case "ratechange":
      case "reset":
      case "seeked":
      case "submit":
      case "toggle":
      case "touchcancel":
      case "touchend":
      case "touchstart":
      case "volumechange":
      case "change":
      case "selectionchange":
      case "textInput":
      case "compositionstart":
      case "compositionend":
      case "compositionupdate":
      case "beforeblur":
      case "afterblur":
      case "beforeinput":
      case "blur":
      case "fullscreenchange":
      case "fullscreenerror":
      case "focus":
      case "hashchange":
      case "popstate":
      case "select":
      case "selectstart":
        return 2;
      case "drag":
      case "dragenter":
      case "dragexit":
      case "dragleave":
      case "dragover":
      case "mousemove":
      case "mouseout":
      case "mouseover":
      case "pointermove":
      case "pointerout":
      case "pointerover":
      case "resize":
      case "scroll":
      case "touchmove":
      case "wheel":
      case "mouseenter":
      case "mouseleave":
      case "pointerenter":
      case "pointerleave":
        return 8;
      case "message":
        switch (rm()) {
          case xs:
            return 2;
          case Hs:
            return 8;
          case on:
          case ym:
            return 32;
          case ps:
            return 268435456;
          default:
            return 32;
        }
      default:
        return 32;
    }
  }
  var Ss = !1, Va = null, Za = null, La = null, tn = /* @__PURE__ */ new Map(), an = /* @__PURE__ */ new Map(), Ka = [], uy = "mousedown mouseup touchcancel touchend touchstart auxclick dblclick pointercancel pointerdown pointerup dragend dragstart drop compositionend compositionstart keydown keypress keyup input textInput copy cut paste click change contextmenu reset".split(
    " "
  );
  function Wd(l, t) {
    switch (l) {
      case "focusin":
      case "focusout":
        Va = null;
        break;
      case "dragenter":
      case "dragleave":
        Za = null;
        break;
      case "mouseover":
      case "mouseout":
        La = null;
        break;
      case "pointerover":
      case "pointerout":
        tn.delete(t.pointerId);
        break;
      case "gotpointercapture":
      case "lostpointercapture":
        an.delete(t.pointerId);
    }
  }
  function un(l, t, a, u, e, n) {
    return l === null || l.nativeEvent !== n ? (l = {
      blockedOn: t,
      domEventName: a,
      eventSystemFlags: u,
      nativeEvent: n,
      targetContainers: [e]
    }, t !== null && (t = zu(t), t !== null && wd(t)), l) : (l.eventSystemFlags |= u, t = l.targetContainers, e !== null && t.indexOf(e) === -1 && t.push(e), l);
  }
  function ey(l, t, a, u, e) {
    switch (t) {
      case "focusin":
        return Va = un(
          Va,
          l,
          t,
          a,
          u,
          e
        ), !0;
      case "dragenter":
        return Za = un(
          Za,
          l,
          t,
          a,
          u,
          e
        ), !0;
      case "mouseover":
        return La = un(
          La,
          l,
          t,
          a,
          u,
          e
        ), !0;
      case "pointerover":
        var n = e.pointerId;
        return tn.set(
          n,
          un(
            tn.get(n) || null,
            l,
            t,
            a,
            u,
            e
          )
        ), !0;
      case "gotpointercapture":
        return n = e.pointerId, an.set(
          n,
          un(
            an.get(n) || null,
            l,
            t,
            a,
            u,
            e
          )
        ), !0;
    }
    return !1;
  }
  function kd(l) {
    var t = ka(l.target);
    if (t !== null) {
      var a = U(t);
      if (a !== null) {
        if (t = a.tag, t === 13) {
          if (t = Al(a), t !== null) {
            l.blockedOn = t, Vs(l.priority, function() {
              Fd(a);
            });
            return;
          }
        } else if (t === 31) {
          if (t = $l(a), t !== null) {
            l.blockedOn = t, Vs(l.priority, function() {
              Fd(a);
            });
            return;
          }
        } else if (t === 3 && a.stateNode.current.memoizedState.isDehydrated) {
          l.blockedOn = a.tag === 3 ? a.stateNode.containerInfo : null;
          return;
        }
      }
    }
    l.blockedOn = null;
  }
  function Ri(l) {
    if (l.blockedOn !== null) return !1;
    for (var t = l.targetContainers; 0 < t.length; ) {
      var a = ys(l.nativeEvent);
      if (a === null) {
        a = l.nativeEvent;
        var u = new a.constructor(
          a.type,
          a
        );
        Pi = u, a.target.dispatchEvent(u), Pi = null;
      } else
        return t = zu(a), t !== null && wd(t), l.blockedOn = a, !1;
      t.shift();
    }
    return !0;
  }
  function Id(l, t, a) {
    Ri(l) && a.delete(t);
  }
  function ny() {
    Ss = !1, Va !== null && Ri(Va) && (Va = null), Za !== null && Ri(Za) && (Za = null), La !== null && Ri(La) && (La = null), tn.forEach(Id), an.forEach(Id);
  }
  function xi(l, t) {
    l.blockedOn === t && (l.blockedOn = null, Ss || (Ss = !0, z.unstable_scheduleCallback(
      z.unstable_NormalPriority,
      ny
    )));
  }
  var Hi = null;
  function Pd(l) {
    Hi !== l && (Hi = l, z.unstable_scheduleCallback(
      z.unstable_NormalPriority,
      function() {
        Hi === l && (Hi = null);
        for (var t = 0; t < l.length; t += 3) {
          var a = l[t], u = l[t + 1], e = l[t + 2];
          if (typeof u != "function") {
            if (gs(u || a) === null)
              continue;
            break;
          }
          var n = zu(a);
          n !== null && (l.splice(t, 3), t -= 3, Ic(
            n,
            {
              pending: !0,
              data: e,
              method: a.method,
              action: u
            },
            u,
            e
          ));
        }
      }
    ));
  }
  function se(l) {
    function t(f) {
      return xi(f, l);
    }
    Va !== null && xi(Va, l), Za !== null && xi(Za, l), La !== null && xi(La, l), tn.forEach(t), an.forEach(t);
    for (var a = 0; a < Ka.length; a++) {
      var u = Ka[a];
      u.blockedOn === l && (u.blockedOn = null);
    }
    for (; 0 < Ka.length && (a = Ka[0], a.blockedOn === null); )
      kd(a), a.blockedOn === null && Ka.shift();
    if (a = (l.ownerDocument || l).$$reactFormReplay, a != null)
      for (u = 0; u < a.length; u += 3) {
        var e = a[u], n = a[u + 1], i = e[kl] || null;
        if (typeof n == "function")
          i || Pd(a);
        else if (i) {
          var c = null;
          if (n && n.hasAttribute("formAction")) {
            if (e = n, i = n[kl] || null)
              c = i.formAction;
            else if (gs(e) !== null) continue;
          } else c = i.action;
          typeof c == "function" ? a[u + 1] = c : (a.splice(u, 3), u -= 3), Pd(a);
        }
      }
  }
  function lm() {
    function l(n) {
      n.canIntercept && n.info === "react-transition" && n.intercept({
        handler: function() {
          return new Promise(function(i) {
            return e = i;
          });
        },
        focusReset: "manual",
        scroll: "manual"
      });
    }
    function t() {
      e !== null && (e(), e = null), u || setTimeout(a, 20);
    }
    function a() {
      if (!u && !navigation.transition) {
        var n = navigation.currentEntry;
        n && n.url != null && navigation.navigate(n.url, {
          state: n.getState(),
          info: "react-transition",
          history: "replace"
        });
      }
    }
    if (typeof navigation == "object") {
      var u = !1, e = null;
      return navigation.addEventListener("navigate", l), navigation.addEventListener("navigatesuccess", t), navigation.addEventListener("navigateerror", t), setTimeout(a, 100), function() {
        u = !0, navigation.removeEventListener("navigate", l), navigation.removeEventListener("navigatesuccess", t), navigation.removeEventListener("navigateerror", t), e !== null && (e(), e = null);
      };
    }
  }
  function Ts(l) {
    this._internalRoot = l;
  }
  pi.prototype.render = Ts.prototype.render = function(l) {
    var t = this._internalRoot;
    if (t === null) throw Error(r(409));
    var a = t.current, u = gt();
    Kd(a, u, l, t, null, null);
  }, pi.prototype.unmount = Ts.prototype.unmount = function() {
    var l = this._internalRoot;
    if (l !== null) {
      this._internalRoot = null;
      var t = l.containerInfo;
      Kd(l.current, 2, null, l, null, null), Ti(), t[bu] = null;
    }
  };
  function pi(l) {
    this._internalRoot = l;
  }
  pi.prototype.unstable_scheduleHydration = function(l) {
    if (l) {
      var t = Qs();
      l = { blockedOn: null, target: l, priority: t };
      for (var a = 0; a < Ka.length && t !== 0 && t < Ka[a].priority; a++) ;
      Ka.splice(a, 0, l), a === 0 && kd(l);
    }
  };
  var tm = B.version;
  if (tm !== "19.3.0")
    throw Error(
      r(
        527,
        tm,
        "19.3.0"
      )
    );
  G.findDOMNode = function(l) {
    var t = l._reactInternals;
    if (t === void 0)
      throw typeof l.render == "function" ? Error(r(188)) : (l = Object.keys(l).join(","), Error(r(268, l)));
    return l = pl(t), l = l !== null ? p(l) : null, l = l === null ? null : l.stateNode, l;
  };
  var iy = {
    bundleType: 0,
    version: "19.3.0",
    rendererPackageName: "react-dom",
    currentDispatcherRef: M,
    reconcilerVersion: "19.3.0"
  };
  if (typeof __REACT_DEVTOOLS_GLOBAL_HOOK__ < "u") {
    var Yi = __REACT_DEVTOOLS_GLOBAL_HOOK__;
    if (!Yi.isDisabled && Yi.supportsFiber)
      try {
        ve = Yi.inject(
          iy
        ), st = Yi;
      } catch {
      }
  }
  return nn.createRoot = function(l, t) {
    if (!I(l)) throw Error(r(299));
    var a = !1, u = "", e = L0, n = K0, i = J0;
    return t != null && (t.unstable_strictMode === !0 && (a = !0), t.identifierPrefix !== void 0 && (u = t.identifierPrefix), t.onUncaughtError !== void 0 && (e = t.onUncaughtError), t.onCaughtError !== void 0 && (n = t.onCaughtError), t.onRecoverableError !== void 0 && (i = t.onRecoverableError)), t = Zd(
      l,
      1,
      !1,
      null,
      null,
      a,
      u,
      null,
      e,
      n,
      i,
      lm
    ), l[bu] = t.current, $f(l), new Ts(t);
  }, nn.hydrateRoot = function(l, t, a) {
    if (!I(l)) throw Error(r(299));
    var u = !1, e = "", n = L0, i = K0, c = J0, f = null;
    return a != null && (a.unstable_strictMode === !0 && (u = !0), a.identifierPrefix !== void 0 && (e = a.identifierPrefix), a.onUncaughtError !== void 0 && (n = a.onUncaughtError), a.onCaughtError !== void 0 && (i = a.onCaughtError), a.onRecoverableError !== void 0 && (c = a.onRecoverableError), a.formState !== void 0 && (f = a.formState)), t = Zd(
      l,
      1,
      !0,
      t,
      a ?? null,
      u,
      e,
      f,
      n,
      i,
      c,
      lm
    ), t.context = Ld(null), a = t.current, u = gt(), u = wi(u), e = ja(u), e.callback = null, Ua(a, e, u), a = u, t.current.lanes = a, me(t, a), Pt(t), l[bu] = t.current, $f(l), new pi(t);
  }, nn.version = "19.3.0", nn;
}
var vm;
function yy() {
  if (vm) return zs.exports;
  vm = 1;
  function z() {
    if (!(typeof __REACT_DEVTOOLS_GLOBAL_HOOK__ > "u" || typeof __REACT_DEVTOOLS_GLOBAL_HOOK__.checkDCE != "function"))
      try {
        __REACT_DEVTOOLS_GLOBAL_HOOK__.checkDCE(z);
      } catch (B) {
        console.error(B);
      }
  }
  return z(), zs.exports = ry(), zs.exports;
}
var gy = yy();
const ut = new Intl.NumberFormat(), Sy = new Intl.DateTimeFormat(void 0, { month: "short", day: "numeric", year: "numeric" }), Ty = {
  Available: "#3b82f6",
  Deployed: "#10b981",
  Repair: "#f59e0b",
  Retired: "#94a3b8"
};
function Ml({ name: z, size: B = 20 }) {
  const Q = {
    assets: /* @__PURE__ */ o.jsxs(o.Fragment, { children: [
      /* @__PURE__ */ o.jsx("path", { d: "m12 3 9 4.5v9L12 21l-9-4.5v-9L12 3Z" }),
      /* @__PURE__ */ o.jsx("path", { d: "m3.5 7.5 8.5 4.3 8.5-4.3M12 12v9" })
    ] }),
    deployed: /* @__PURE__ */ o.jsxs(o.Fragment, { children: [
      /* @__PURE__ */ o.jsx("path", { d: "m5 12 4 4L19 6" }),
      /* @__PURE__ */ o.jsx("circle", { cx: "12", cy: "12", r: "9" })
    ] }),
    service: /* @__PURE__ */ o.jsxs(o.Fragment, { children: [
      /* @__PURE__ */ o.jsx("path", { d: "M12 8v4l3 2" }),
      /* @__PURE__ */ o.jsx("circle", { cx: "12", cy: "12", r: "9" })
    ] }),
    stock: /* @__PURE__ */ o.jsx(o.Fragment, { children: /* @__PURE__ */ o.jsx("path", { d: "M4 7h16v13H4zM8 7V4h8v3M8 12h8M8 16h5" }) }),
    warranty: /* @__PURE__ */ o.jsxs(o.Fragment, { children: [
      /* @__PURE__ */ o.jsx("path", { d: "M12 3 5 6v5c0 4.5 2.8 7.8 7 10 4.2-2.2 7-5.5 7-10V6l-7-3Z" }),
      /* @__PURE__ */ o.jsx("path", { d: "m9 12 2 2 4-4" })
    ] }),
    license: /* @__PURE__ */ o.jsxs(o.Fragment, { children: [
      /* @__PURE__ */ o.jsx("rect", { x: "4", y: "3", width: "16", height: "18", rx: "2" }),
      /* @__PURE__ */ o.jsx("path", { d: "M8 8h8M8 12h5M8 16h8" })
    ] }),
    refresh: /* @__PURE__ */ o.jsxs(o.Fragment, { children: [
      /* @__PURE__ */ o.jsx("path", { d: "M20 7v5h-5M4 17v-5h5" }),
      /* @__PURE__ */ o.jsx("path", { d: "M5.6 9a7 7 0 0 1 11.6-2L20 12M4 12l2.8 5a7 7 0 0 0 11.6-2" })
    ] }),
    arrow: /* @__PURE__ */ o.jsx(o.Fragment, { children: /* @__PURE__ */ o.jsx("path", { d: "M7 17 17 7M7 7h10v10" }) })
  };
  return /* @__PURE__ */ o.jsx(
    "svg",
    {
      width: B,
      height: B,
      viewBox: "0 0 24 24",
      fill: "none",
      stroke: "currentColor",
      strokeWidth: "1.8",
      strokeLinecap: "round",
      strokeLinejoin: "round",
      "aria-hidden": "true",
      children: Q[z] ?? Q.assets
    }
  );
}
function As(z) {
  if (!z) return "Not scheduled";
  const B = /* @__PURE__ */ new Date(`${z.slice(0, 10)}T00:00:00`);
  return Number.isNaN(B.getTime()) ? "Date unavailable" : Sy.format(B);
}
function Ey(z) {
  const B = z.getHours();
  return B < 12 ? "Good morning" : B < 18 ? "Good afternoon" : "Good evening";
}
function qi({
  label: z,
  value: B,
  hint: Q,
  icon: r,
  tone: I,
  href: U
}) {
  return /* @__PURE__ */ o.jsxs("a", { className: "dash-metric", href: U, children: [
    /* @__PURE__ */ o.jsx("div", { className: `dash-metric-icon ${I}`, children: /* @__PURE__ */ o.jsx(Ml, { name: r, size: 19 }) }),
    /* @__PURE__ */ o.jsxs("div", { className: "dash-metric-copy", children: [
      /* @__PURE__ */ o.jsx("span", { children: z }),
      /* @__PURE__ */ o.jsx("strong", { children: ut.format(B) }),
      /* @__PURE__ */ o.jsx("small", { children: Q })
    ] }),
    /* @__PURE__ */ o.jsx("span", { className: "dash-metric-link", "aria-hidden": "true", children: /* @__PURE__ */ o.jsx(Ml, { name: "arrow", size: 16 }) })
  ] });
}
function by({ categories: z }) {
  const [B, Q] = Us.useState(!1), r = B ? z : z.slice(0, 7), I = Math.max(1, ...r.map((U) => U.count));
  return z.length === 0 ? /* @__PURE__ */ o.jsx("div", { className: "dash-empty", children: "Add asset categories and inventory to see the distribution." }) : /* @__PURE__ */ o.jsxs("div", { className: "dash-category-chart", children: [
    r.map((U, Al) => /* @__PURE__ */ o.jsxs("div", { className: "dash-bar-row", children: [
      /* @__PURE__ */ o.jsx("span", { className: "dash-bar-label", title: U.name, children: U.name }),
      /* @__PURE__ */ o.jsx("div", { className: "dash-bar-track", children: /* @__PURE__ */ o.jsx("div", { className: "dash-bar-fill", style: {
        width: `${Math.max(U.count > 0 ? 3 : 0, U.count / I * 100)}%`,
        opacity: 1 - Al * 0.045
      } }) }),
      /* @__PURE__ */ o.jsx("span", { className: "dash-bar-value", children: ut.format(U.count) })
    ] }, U.name)),
    z.length > 7 && /* @__PURE__ */ o.jsx("button", { className: "dash-text-button", type: "button", onClick: () => Q(!B), children: B ? "Show fewer" : `View all ${z.length} categories` })
  ] });
}
function zy({ statuses: z, total: B }) {
  const Q = Us.useMemo(() => {
    let r = 0;
    const I = 2 * Math.PI * 43;
    return z.map((U) => {
      const Al = B > 0 ? U.count / B * I : 0, $l = { ...U, length: Al, offset: r, color: Ty[U.name] ?? "#64748b" };
      return r += Al, $l;
    });
  }, [z, B]);
  return /* @__PURE__ */ o.jsxs("div", { className: "dash-status-wrap", children: [
    /* @__PURE__ */ o.jsxs("div", { className: "dash-donut", children: [
      /* @__PURE__ */ o.jsxs("svg", { viewBox: "0 0 112 112", role: "img", "aria-label": `Asset status distribution across ${ut.format(B)} assets`, children: [
        /* @__PURE__ */ o.jsx("circle", { cx: "56", cy: "56", r: "43", fill: "none", stroke: "#eef2f7", strokeWidth: "12" }),
        /* @__PURE__ */ o.jsx("g", { transform: "rotate(-90 56 56)", children: Q.map((r) => /* @__PURE__ */ o.jsx(
          "circle",
          {
            cx: "56",
            cy: "56",
            r: "43",
            fill: "none",
            stroke: r.color,
            strokeWidth: "12",
            strokeDasharray: `${r.length} ${2 * Math.PI * 43 - r.length}`,
            strokeDashoffset: -r.offset
          },
          r.name
        )) })
      ] }),
      /* @__PURE__ */ o.jsxs("div", { className: "dash-donut-center", children: [
        /* @__PURE__ */ o.jsx("strong", { children: ut.format(B) }),
        /* @__PURE__ */ o.jsx("span", { children: "assets" })
      ] })
    ] }),
    /* @__PURE__ */ o.jsxs("div", { className: "dash-legend", children: [
      Q.map((r) => /* @__PURE__ */ o.jsxs("div", { className: "dash-legend-item", children: [
        /* @__PURE__ */ o.jsx("span", { className: "dash-legend-dot", style: { backgroundColor: r.color } }),
        /* @__PURE__ */ o.jsx("span", { children: r.name }),
        /* @__PURE__ */ o.jsx("strong", { children: ut.format(r.count) })
      ] }, r.name)),
      z.length === 0 && /* @__PURE__ */ o.jsx("p", { className: "dash-empty", children: "No asset statuses to show yet." })
    ] })
  ] });
}
const Ds = [
  { key: "service", label: "Service", metric: "serviceAlerts", href: "maintenance.php" },
  { key: "warranty", label: "Warranty", metric: "warrantyAlerts", href: "reports.php?type=warranty" },
  { key: "stock", label: "Low stock", metric: "lowStockItems", href: "supplies.php" },
  { key: "licenses", label: "Licenses", metric: "renewalsDue", href: "software_licenses.php" }
];
function Ny({ data: z }) {
  const [B, Q] = Us.useState("service"), r = Ds.find((U) => U.key === B) ?? Ds[0], I = z.alerts[r.key];
  return /* @__PURE__ */ o.jsxs("section", { className: "dash-panel dash-alert-panel", children: [
    /* @__PURE__ */ o.jsxs("div", { className: "dash-panel-heading", children: [
      /* @__PURE__ */ o.jsxs("div", { children: [
        /* @__PURE__ */ o.jsx("p", { className: "dash-eyebrow", children: "Needs attention" }),
        /* @__PURE__ */ o.jsx("h2", { children: "Operations watchlist" })
      ] }),
      /* @__PURE__ */ o.jsxs("a", { className: "dash-outline-link", href: r.href, children: [
        "Open module ",
        /* @__PURE__ */ o.jsx(Ml, { name: "arrow", size: 14 })
      ] })
    ] }),
    /* @__PURE__ */ o.jsx("div", { className: "dash-tabs", role: "group", "aria-label": "Filter dashboard alerts", children: Ds.map((U) => /* @__PURE__ */ o.jsxs(
      "button",
      {
        type: "button",
        "aria-pressed": B === U.key,
        className: B === U.key ? "is-active" : "",
        onClick: () => Q(U.key),
        children: [
          U.label,
          /* @__PURE__ */ o.jsx("span", { children: ut.format(z.metrics[U.metric]) })
        ]
      },
      U.key
    )) }),
    /* @__PURE__ */ o.jsxs("div", { className: "dash-alert-list", children: [
      I.map((U) => /* @__PURE__ */ o.jsxs("div", { className: "dash-alert-row", children: [
        /* @__PURE__ */ o.jsx("span", { className: `dash-alert-icon ${r.key}`, children: /* @__PURE__ */ o.jsx(Ml, { name: r.key === "stock" ? "stock" : r.key === "licenses" ? "license" : r.key, size: 17 }) }),
        /* @__PURE__ */ o.jsxs("div", { className: "dash-alert-main", children: [
          /* @__PURE__ */ o.jsx("strong", { children: U.title }),
          /* @__PURE__ */ o.jsx("span", { children: U.detail || "Details not provided" })
        ] }),
        /* @__PURE__ */ o.jsx("div", { className: "dash-alert-meta", children: r.key === "stock" ? /* @__PURE__ */ o.jsxs(o.Fragment, { children: [
          /* @__PURE__ */ o.jsxs("strong", { className: (U.quantity ?? 0) <= 0 ? "is-critical" : "", children: [
            U.quantity,
            " on hand"
          ] }),
          /* @__PURE__ */ o.jsxs("span", { children: [
            "Min. ",
            U.minimum
          ] })
        ] }) : r.key === "licenses" ? /* @__PURE__ */ o.jsxs(o.Fragment, { children: [
          /* @__PURE__ */ o.jsx("strong", { children: U.renewalDate ? `Renewal ${As(U.renewalDate)}` : `Update ${As(U.updateDate)}` }),
          /* @__PURE__ */ o.jsx("span", { children: U.detail })
        ] }) : /* @__PURE__ */ o.jsxs(o.Fragment, { children: [
          /* @__PURE__ */ o.jsx("strong", { children: As(U.date) }),
          /* @__PURE__ */ o.jsx("span", { children: r.key === "service" ? U.status : "Warranty expiry" })
        ] }) })
      ] }, `${r.key}-${U.title}`)),
      I.length === 0 && /* @__PURE__ */ o.jsxs("div", { className: "dash-clear-state", children: [
        /* @__PURE__ */ o.jsx("span", { children: /* @__PURE__ */ o.jsx(Ml, { name: "deployed", size: 20 }) }),
        /* @__PURE__ */ o.jsx("strong", { children: "All clear" }),
        /* @__PURE__ */ o.jsxs("p", { children: [
          "No ",
          r.label.toLowerCase(),
          " alerts to show right now."
        ] })
      ] })
    ] })
  ] });
}
function _y({ data: z }) {
  const B = new Date(z.generatedAt), Q = z.metrics.totalAssets > 0 ? Math.round(z.metrics.deployedAssets / z.metrics.totalAssets * 100) : 0, r = new Intl.DateTimeFormat(void 0, {
    weekday: "long",
    month: "long",
    day: "numeric"
  }).format(B), I = new Intl.DateTimeFormat(void 0, {
    hour: "numeric",
    minute: "2-digit"
  }).format(B);
  return /* @__PURE__ */ o.jsxs("div", { className: "dash-app", children: [
    /* @__PURE__ */ o.jsxs("section", { className: "dash-hero", children: [
      /* @__PURE__ */ o.jsxs("div", { className: "dash-hero-content", children: [
        /* @__PURE__ */ o.jsxs("span", { className: "dash-hero-label", children: [
          /* @__PURE__ */ o.jsx("span", {}),
          " LIVE OPERATIONS OVERVIEW"
        ] }),
        /* @__PURE__ */ o.jsxs("h1", { children: [
          Ey(B),
          ", ",
          z.staffName,
          "."
        ] }),
        /* @__PURE__ */ o.jsx("p", { children: "Your IT estate at a glance. Here is what needs your attention today." }),
        /* @__PURE__ */ o.jsxs("div", { className: "dash-hero-meta", children: [
          /* @__PURE__ */ o.jsx("span", { children: r }),
          /* @__PURE__ */ o.jsx("i", {}),
          /* @__PURE__ */ o.jsxs("span", { children: [
            "Updated ",
            I
          ] })
        ] })
      ] }),
      /* @__PURE__ */ o.jsxs("div", { className: "dash-hero-actions", children: [
        /* @__PURE__ */ o.jsxs("a", { className: "dash-primary-action", href: "inventory.php", children: [
          "View inventory ",
          /* @__PURE__ */ o.jsx(Ml, { name: "arrow", size: 16 })
        ] }),
        /* @__PURE__ */ o.jsxs("a", { className: "dash-secondary-action", href: "issuance.php", children: [
          "New issuance ",
          /* @__PURE__ */ o.jsx("span", { children: "+" })
        ] }),
        /* @__PURE__ */ o.jsxs("button", { className: "dash-refresh-action", type: "button", onClick: () => window.location.reload(), "aria-label": "Refresh dashboard", children: [
          /* @__PURE__ */ o.jsx(Ml, { name: "refresh", size: 16 }),
          " Refresh data"
        ] })
      ] }),
      /* @__PURE__ */ o.jsx("div", { className: "dash-hero-orbit dash-orbit-one" }),
      /* @__PURE__ */ o.jsx("div", { className: "dash-hero-orbit dash-orbit-two" })
    ] }),
    /* @__PURE__ */ o.jsxs("div", { className: "dash-section-heading", children: [
      /* @__PURE__ */ o.jsxs("div", { children: [
        /* @__PURE__ */ o.jsx("p", { className: "dash-eyebrow", children: "ASSET HEALTH" }),
        /* @__PURE__ */ o.jsx("h2", { children: "Inventory snapshot" })
      ] }),
      /* @__PURE__ */ o.jsxs("a", { href: "reports.php", className: "dash-muted-link", children: [
        "View reports ",
        /* @__PURE__ */ o.jsx(Ml, { name: "arrow", size: 14 })
      ] })
    ] }),
    /* @__PURE__ */ o.jsxs("section", { className: "dash-metrics-grid", "aria-label": "Inventory metrics", children: [
      /* @__PURE__ */ o.jsx(qi, { label: "Total assets", value: z.metrics.totalAssets, hint: "Tracked in inventory", icon: "assets", tone: "blue", href: "inventory.php" }),
      /* @__PURE__ */ o.jsx(qi, { label: "Deployed", value: z.metrics.deployedAssets, hint: `${Q}% of all assets`, icon: "deployed", tone: "green", href: "inventory.php" }),
      /* @__PURE__ */ o.jsx(qi, { label: "Service due", value: z.metrics.serviceAlerts, hint: "Overdue or due within 7 days", icon: "service", tone: "amber", href: "maintenance.php" }),
      /* @__PURE__ */ o.jsx(qi, { label: "Low stock", value: z.metrics.lowStockItems, hint: "At or below minimum", icon: "stock", tone: "violet", href: "supplies.php" })
    ] }),
    /* @__PURE__ */ o.jsxs("section", { className: "dash-secondary-metrics", "aria-label": "Additional operational metrics", children: [
      /* @__PURE__ */ o.jsxs("div", { children: [
        /* @__PURE__ */ o.jsx("span", { className: "dash-secondary-icon warranty", children: /* @__PURE__ */ o.jsx(Ml, { name: "warranty", size: 16 }) }),
        /* @__PURE__ */ o.jsx("span", { children: "Warranty due in 30 days" }),
        /* @__PURE__ */ o.jsx("strong", { children: ut.format(z.metrics.warrantyAlerts) })
      ] }),
      /* @__PURE__ */ o.jsxs("div", { children: [
        /* @__PURE__ */ o.jsx("span", { className: "dash-secondary-icon license", children: /* @__PURE__ */ o.jsx(Ml, { name: "license", size: 16 }) }),
        /* @__PURE__ */ o.jsx("span", { children: "License renewals due" }),
        /* @__PURE__ */ o.jsx("strong", { children: ut.format(z.metrics.renewalsDue) })
      ] }),
      /* @__PURE__ */ o.jsxs("div", { children: [
        /* @__PURE__ */ o.jsx("span", { className: "dash-secondary-icon room", children: /* @__PURE__ */ o.jsx(Ml, { name: "assets", size: 16 }) }),
        /* @__PURE__ */ o.jsx("span", { children: "Room deployments · 30 days" }),
        /* @__PURE__ */ o.jsx("strong", { children: ut.format(z.metrics.roomDeployments) })
      ] })
    ] }),
    /* @__PURE__ */ o.jsxs("section", { className: "dash-analytics-grid", children: [
      /* @__PURE__ */ o.jsxs("div", { className: "dash-panel dash-category-panel", children: [
        /* @__PURE__ */ o.jsxs("div", { className: "dash-panel-heading", children: [
          /* @__PURE__ */ o.jsxs("div", { children: [
            /* @__PURE__ */ o.jsx("p", { className: "dash-eyebrow", children: "INVENTORY MIX" }),
            /* @__PURE__ */ o.jsx("h2", { children: "Assets by category" })
          ] }),
          /* @__PURE__ */ o.jsxs("span", { className: "dash-panel-caption", children: [
            z.categories.length,
            " categories"
          ] })
        ] }),
        /* @__PURE__ */ o.jsx(by, { categories: z.categories })
      ] }),
      /* @__PURE__ */ o.jsxs("div", { className: "dash-panel dash-status-panel", children: [
        /* @__PURE__ */ o.jsxs("div", { className: "dash-panel-heading", children: [
          /* @__PURE__ */ o.jsxs("div", { children: [
            /* @__PURE__ */ o.jsx("p", { className: "dash-eyebrow", children: "CURRENT STATE" }),
            /* @__PURE__ */ o.jsx("h2", { children: "Asset status" })
          ] }),
          /* @__PURE__ */ o.jsx("a", { href: "inventory.php", className: "dash-icon-link", "aria-label": "Open inventory", children: /* @__PURE__ */ o.jsx(Ml, { name: "arrow", size: 16 }) })
        ] }),
        /* @__PURE__ */ o.jsx(zy, { statuses: z.statuses, total: z.metrics.totalAssets }),
        /* @__PURE__ */ o.jsxs("div", { className: "dash-utilization", children: [
          /* @__PURE__ */ o.jsxs("div", { className: "dash-utilization-copy", children: [
            /* @__PURE__ */ o.jsx("span", { children: "Deployment ratio" }),
            /* @__PURE__ */ o.jsxs("strong", { children: [
              Q,
              "%"
            ] })
          ] }),
          /* @__PURE__ */ o.jsx("div", { className: "dash-progress", children: /* @__PURE__ */ o.jsx("span", { style: { width: `${Q}%` } }) }),
          /* @__PURE__ */ o.jsxs("p", { children: [
            ut.format(z.metrics.availableAssets),
            " assets currently available"
          ] })
        ] })
      ] })
    ] }),
    /* @__PURE__ */ o.jsxs("section", { className: "dash-bottom-grid", children: [
      /* @__PURE__ */ o.jsx(Ny, { data: z }),
      /* @__PURE__ */ o.jsxs("aside", { className: "dash-panel dash-action-panel", children: [
        /* @__PURE__ */ o.jsx("div", { className: "dash-panel-heading", children: /* @__PURE__ */ o.jsxs("div", { children: [
          /* @__PURE__ */ o.jsx("p", { className: "dash-eyebrow", children: "WORKSPACE" }),
          /* @__PURE__ */ o.jsx("h2", { children: "At a glance" })
        ] }) }),
        /* @__PURE__ */ o.jsxs("div", { className: "dash-glance-card", children: [
          /* @__PURE__ */ o.jsx("span", { className: "dash-glance-icon repair", children: /* @__PURE__ */ o.jsx(Ml, { name: "service", size: 18 }) }),
          /* @__PURE__ */ o.jsxs("div", { children: [
            /* @__PURE__ */ o.jsxs("strong", { children: [
              ut.format(z.metrics.repairAssets),
              " assets"
            ] }),
            /* @__PURE__ */ o.jsx("span", { children: "Currently in repair" })
          ] }),
          /* @__PURE__ */ o.jsx("a", { href: "maintenance.php", "aria-label": "View maintenance", children: /* @__PURE__ */ o.jsx(Ml, { name: "arrow", size: 15 }) })
        ] }),
        /* @__PURE__ */ o.jsxs("div", { className: "dash-glance-card", children: [
          /* @__PURE__ */ o.jsx("span", { className: "dash-glance-icon license", children: /* @__PURE__ */ o.jsx(Ml, { name: "license", size: 18 }) }),
          /* @__PURE__ */ o.jsxs("div", { children: [
            /* @__PURE__ */ o.jsxs("strong", { children: [
              ut.format(z.metrics.activeLicenses),
              " licenses"
            ] }),
            /* @__PURE__ */ o.jsxs("span", { children: [
              ut.format(z.metrics.updatesDue),
              " updates due soon"
            ] })
          ] }),
          /* @__PURE__ */ o.jsx("a", { href: "software_licenses.php", "aria-label": "View software licenses", children: /* @__PURE__ */ o.jsx(Ml, { name: "arrow", size: 15 }) })
        ] }),
        /* @__PURE__ */ o.jsxs("div", { className: "dash-glance-card", children: [
          /* @__PURE__ */ o.jsx("span", { className: "dash-glance-icon retired", children: /* @__PURE__ */ o.jsx(Ml, { name: "assets", size: 18 }) }),
          /* @__PURE__ */ o.jsxs("div", { children: [
            /* @__PURE__ */ o.jsxs("strong", { children: [
              ut.format(z.metrics.retiredAssets),
              " assets"
            ] }),
            /* @__PURE__ */ o.jsx("span", { children: "Retired from service" })
          ] }),
          /* @__PURE__ */ o.jsx("a", { href: "inventory.php", "aria-label": "View inventory", children: /* @__PURE__ */ o.jsx(Ml, { name: "arrow", size: 15 }) })
        ] }),
        /* @__PURE__ */ o.jsxs("a", { className: "dash-full-report", href: "reports.php", children: [
          "Explore all reports ",
          /* @__PURE__ */ o.jsx(Ml, { name: "arrow", size: 15 })
        ] })
      ] })
    ] }),
    /* @__PURE__ */ o.jsx("p", { className: "dash-footnote", children: "Figures are based on records available in this installation at the time shown above." })
  ] });
}
const Ms = document.getElementById("dashboard-root");
if (Ms?.dataset.dashboard) {
  const z = JSON.parse(Ms.dataset.dashboard);
  gy.createRoot(Ms).render(/* @__PURE__ */ o.jsx(_y, { data: z }));
}
