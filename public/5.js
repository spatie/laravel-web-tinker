webpackJsonp([5],{

/***/ "./node_modules/monaco-editor/esm/vs/language/typescript/languageFeatures.js":
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* unused harmony export Adapter */
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "b", function() { return DiagnostcsAdapter; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "j", function() { return SuggestAdapter; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "i", function() { return SignatureHelpAdapter; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "g", function() { return QuickInfoAdapter; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "e", function() { return OccurrencesAdapter; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return DefinitionAdapter; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "h", function() { return ReferenceAdapter; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "f", function() { return OutlineAdapter; });
/* unused harmony export Kind */
/* unused harmony export FormatHelper */
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "c", function() { return FormatAdapter; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "d", function() { return FormatOnTypeAdapter; });
/*---------------------------------------------------------------------------------------------
 *  Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

var __extends = (this && this.__extends) || (function () {
    var extendStatics = function (d, b) {
        extendStatics = Object.setPrototypeOf ||
            ({ __proto__: [] } instanceof Array && function (d, b) { d.__proto__ = b; }) ||
            function (d, b) { for (var p in b) if (b.hasOwnProperty(p)) d[p] = b[p]; };
        return extendStatics(d, b);
    }
    return function (d, b) {
        extendStatics(d, b);
        function __() { this.constructor = d; }
        d.prototype = b === null ? Object.create(b) : (__.prototype = b.prototype, new __());
    };
})();
var Uri = monaco.Uri;
var Promise = monaco.Promise;
//#region utils copied from typescript to prevent loading the entire typescriptServices ---
var IndentStyle;
(function (IndentStyle) {
    IndentStyle[IndentStyle["None"] = 0] = "None";
    IndentStyle[IndentStyle["Block"] = 1] = "Block";
    IndentStyle[IndentStyle["Smart"] = 2] = "Smart";
})(IndentStyle || (IndentStyle = {}));
function flattenDiagnosticMessageText(messageText, newLine) {
    if (typeof messageText === "string") {
        return messageText;
    }
    else {
        var diagnosticChain = messageText;
        var result = "";
        var indent = 0;
        while (diagnosticChain) {
            if (indent) {
                result += newLine;
                for (var i = 0; i < indent; i++) {
                    result += "  ";
                }
            }
            result += diagnosticChain.messageText;
            indent++;
            diagnosticChain = diagnosticChain.next;
        }
        return result;
    }
}
function displayPartsToString(displayParts) {
    if (displayParts) {
        return displayParts.map(function (displayPart) { return displayPart.text; }).join("");
    }
    return "";
}
//#endregion
var Adapter = /** @class */ (function () {
    function Adapter(_worker) {
        this._worker = _worker;
    }
    Adapter.prototype._positionToOffset = function (uri, position) {
        var model = monaco.editor.getModel(uri);
        return model.getOffsetAt(position);
    };
    Adapter.prototype._offsetToPosition = function (uri, offset) {
        var model = monaco.editor.getModel(uri);
        return model.getPositionAt(offset);
    };
    Adapter.prototype._textSpanToRange = function (uri, span) {
        var p1 = this._offsetToPosition(uri, span.start);
        var p2 = this._offsetToPosition(uri, span.start + span.length);
        var startLineNumber = p1.lineNumber, startColumn = p1.column;
        var endLineNumber = p2.lineNumber, endColumn = p2.column;
        return { startLineNumber: startLineNumber, startColumn: startColumn, endLineNumber: endLineNumber, endColumn: endColumn };
    };
    return Adapter;
}());

// --- diagnostics --- ---
var DiagnostcsAdapter = /** @class */ (function (_super) {
    __extends(DiagnostcsAdapter, _super);
    function DiagnostcsAdapter(_defaults, _selector, worker) {
        var _this = _super.call(this, worker) || this;
        _this._defaults = _defaults;
        _this._selector = _selector;
        _this._disposables = [];
        _this._listener = Object.create(null);
        var onModelAdd = function (model) {
            if (model.getModeId() !== _selector) {
                return;
            }
            var handle;
            var changeSubscription = model.onDidChangeContent(function () {
                clearTimeout(handle);
                handle = setTimeout(function () { return _this._doValidate(model.uri); }, 500);
            });
            _this._listener[model.uri.toString()] = {
                dispose: function () {
                    changeSubscription.dispose();
                    clearTimeout(handle);
                }
            };
            _this._doValidate(model.uri);
        };
        var onModelRemoved = function (model) {
            monaco.editor.setModelMarkers(model, _this._selector, []);
            var key = model.uri.toString();
            if (_this._listener[key]) {
                _this._listener[key].dispose();
                delete _this._listener[key];
            }
        };
        _this._disposables.push(monaco.editor.onDidCreateModel(onModelAdd));
        _this._disposables.push(monaco.editor.onWillDisposeModel(onModelRemoved));
        _this._disposables.push(monaco.editor.onDidChangeModelLanguage(function (event) {
            onModelRemoved(event.model);
            onModelAdd(event.model);
        }));
        _this._disposables.push({
            dispose: function () {
                for (var _i = 0, _a = monaco.editor.getModels(); _i < _a.length; _i++) {
                    var model = _a[_i];
                    onModelRemoved(model);
                }
            }
        });
        _this._disposables.push(_this._defaults.onDidChange(function () {
            // redo diagnostics when options change
            for (var _i = 0, _a = monaco.editor.getModels(); _i < _a.length; _i++) {
                var model = _a[_i];
                onModelRemoved(model);
                onModelAdd(model);
            }
        }));
        monaco.editor.getModels().forEach(onModelAdd);
        return _this;
    }
    DiagnostcsAdapter.prototype.dispose = function () {
        this._disposables.forEach(function (d) { return d && d.dispose(); });
        this._disposables = [];
    };
    DiagnostcsAdapter.prototype._doValidate = function (resource) {
        var _this = this;
        this._worker(resource).then(function (worker) {
            if (!monaco.editor.getModel(resource)) {
                // model was disposed in the meantime
                return null;
            }
            var promises = [];
            var _a = _this._defaults.getDiagnosticsOptions(), noSyntaxValidation = _a.noSyntaxValidation, noSemanticValidation = _a.noSemanticValidation;
            if (!noSyntaxValidation) {
                promises.push(worker.getSyntacticDiagnostics(resource.toString()));
            }
            if (!noSemanticValidation) {
                promises.push(worker.getSemanticDiagnostics(resource.toString()));
            }
            return Promise.join(promises);
        }).then(function (diagnostics) {
            if (!diagnostics || !monaco.editor.getModel(resource)) {
                // model was disposed in the meantime
                return null;
            }
            var markers = diagnostics
                .reduce(function (p, c) { return c.concat(p); }, [])
                .map(function (d) { return _this._convertDiagnostics(resource, d); });
            monaco.editor.setModelMarkers(monaco.editor.getModel(resource), _this._selector, markers);
        }).then(undefined, function (err) {
            console.error(err);
        });
    };
    DiagnostcsAdapter.prototype._convertDiagnostics = function (resource, diag) {
        var _a = this._offsetToPosition(resource, diag.start), startLineNumber = _a.lineNumber, startColumn = _a.column;
        var _b = this._offsetToPosition(resource, diag.start + diag.length), endLineNumber = _b.lineNumber, endColumn = _b.column;
        return {
            severity: monaco.MarkerSeverity.Error,
            startLineNumber: startLineNumber,
            startColumn: startColumn,
            endLineNumber: endLineNumber,
            endColumn: endColumn,
            message: flattenDiagnosticMessageText(diag.messageText, '\n')
        };
    };
    return DiagnostcsAdapter;
}(Adapter));

var SuggestAdapter = /** @class */ (function (_super) {
    __extends(SuggestAdapter, _super);
    function SuggestAdapter() {
        return _super !== null && _super.apply(this, arguments) || this;
    }
    Object.defineProperty(SuggestAdapter.prototype, "triggerCharacters", {
        get: function () {
            return ['.'];
        },
        enumerable: true,
        configurable: true
    });
    SuggestAdapter.prototype.provideCompletionItems = function (model, position, _context, token) {
        var wordInfo = model.getWordUntilPosition(position);
        var resource = model.uri;
        var offset = this._positionToOffset(resource, position);
        return this._worker(resource).then(function (worker) {
            return worker.getCompletionsAtPosition(resource.toString(), offset);
        }).then(function (info) {
            if (!info) {
                return;
            }
            var suggestions = info.entries.map(function (entry) {
                return {
                    uri: resource,
                    position: position,
                    label: entry.name,
                    insertText: entry.name,
                    sortText: entry.sortText,
                    kind: SuggestAdapter.convertKind(entry.kind)
                };
            });
            return {
                suggestions: suggestions
            };
        });
    };
    SuggestAdapter.prototype.resolveCompletionItem = function (_model, _position, item, token) {
        var _this = this;
        var myItem = item;
        var resource = myItem.uri;
        var position = myItem.position;
        return this._worker(resource).then(function (worker) {
            return worker.getCompletionEntryDetails(resource.toString(), _this._positionToOffset(resource, position), myItem.label);
        }).then(function (details) {
            if (!details) {
                return myItem;
            }
            return {
                uri: resource,
                position: position,
                label: details.name,
                kind: SuggestAdapter.convertKind(details.kind),
                detail: displayPartsToString(details.displayParts),
                documentation: {
                    value: displayPartsToString(details.documentation)
                }
            };
        });
    };
    SuggestAdapter.convertKind = function (kind) {
        switch (kind) {
            case Kind.primitiveType:
            case Kind.keyword:
                return monaco.languages.CompletionItemKind.Keyword;
            case Kind.variable:
            case Kind.localVariable:
                return monaco.languages.CompletionItemKind.Variable;
            case Kind.memberVariable:
            case Kind.memberGetAccessor:
            case Kind.memberSetAccessor:
                return monaco.languages.CompletionItemKind.Field;
            case Kind.function:
            case Kind.memberFunction:
            case Kind.constructSignature:
            case Kind.callSignature:
            case Kind.indexSignature:
                return monaco.languages.CompletionItemKind.Function;
            case Kind.enum:
                return monaco.languages.CompletionItemKind.Enum;
            case Kind.module:
                return monaco.languages.CompletionItemKind.Module;
            case Kind.class:
                return monaco.languages.CompletionItemKind.Class;
            case Kind.interface:
                return monaco.languages.CompletionItemKind.Interface;
            case Kind.warning:
                return monaco.languages.CompletionItemKind.File;
        }
        return monaco.languages.CompletionItemKind.Property;
    };
    return SuggestAdapter;
}(Adapter));

var SignatureHelpAdapter = /** @class */ (function (_super) {
    __extends(SignatureHelpAdapter, _super);
    function SignatureHelpAdapter() {
        var _this = _super !== null && _super.apply(this, arguments) || this;
        _this.signatureHelpTriggerCharacters = ['(', ','];
        return _this;
    }
    SignatureHelpAdapter.prototype.provideSignatureHelp = function (model, position, token) {
        var _this = this;
        var resource = model.uri;
        return this._worker(resource).then(function (worker) { return worker.getSignatureHelpItems(resource.toString(), _this._positionToOffset(resource, position)); }).then(function (info) {
            if (!info) {
                return;
            }
            var ret = {
                activeSignature: info.selectedItemIndex,
                activeParameter: info.argumentIndex,
                signatures: []
            };
            info.items.forEach(function (item) {
                var signature = {
                    label: '',
                    documentation: null,
                    parameters: []
                };
                signature.label += displayPartsToString(item.prefixDisplayParts);
                item.parameters.forEach(function (p, i, a) {
                    var label = displayPartsToString(p.displayParts);
                    var parameter = {
                        label: label,
                        documentation: displayPartsToString(p.documentation)
                    };
                    signature.label += label;
                    signature.parameters.push(parameter);
                    if (i < a.length - 1) {
                        signature.label += displayPartsToString(item.separatorDisplayParts);
                    }
                });
                signature.label += displayPartsToString(item.suffixDisplayParts);
                ret.signatures.push(signature);
            });
            return ret;
        });
    };
    return SignatureHelpAdapter;
}(Adapter));

// --- hover ------
var QuickInfoAdapter = /** @class */ (function (_super) {
    __extends(QuickInfoAdapter, _super);
    function QuickInfoAdapter() {
        return _super !== null && _super.apply(this, arguments) || this;
    }
    QuickInfoAdapter.prototype.provideHover = function (model, position, token) {
        var _this = this;
        var resource = model.uri;
        return this._worker(resource).then(function (worker) {
            return worker.getQuickInfoAtPosition(resource.toString(), _this._positionToOffset(resource, position));
        }).then(function (info) {
            if (!info) {
                return;
            }
            var documentation = displayPartsToString(info.documentation);
            var tags = info.tags ? info.tags.map(function (tag) {
                var label = "*@" + tag.name + "*";
                if (!tag.text) {
                    return label;
                }
                return label + (tag.text.match(/\r\n|\n/g) ? ' \n' + tag.text : " - " + tag.text);
            })
                .join('  \n\n') : '';
            var contents = displayPartsToString(info.displayParts);
            return {
                range: _this._textSpanToRange(resource, info.textSpan),
                contents: [{
                        value: '```js\n' + contents + '\n```\n'
                    }, {
                        value: documentation + (tags ? '\n\n' + tags : '')
                    }]
            };
        });
    };
    return QuickInfoAdapter;
}(Adapter));

// --- occurrences ------
var OccurrencesAdapter = /** @class */ (function (_super) {
    __extends(OccurrencesAdapter, _super);
    function OccurrencesAdapter() {
        return _super !== null && _super.apply(this, arguments) || this;
    }
    OccurrencesAdapter.prototype.provideDocumentHighlights = function (model, position, token) {
        var _this = this;
        var resource = model.uri;
        return this._worker(resource).then(function (worker) {
            return worker.getOccurrencesAtPosition(resource.toString(), _this._positionToOffset(resource, position));
        }).then(function (entries) {
            if (!entries) {
                return;
            }
            return entries.map(function (entry) {
                return {
                    range: _this._textSpanToRange(resource, entry.textSpan),
                    kind: entry.isWriteAccess ? monaco.languages.DocumentHighlightKind.Write : monaco.languages.DocumentHighlightKind.Text
                };
            });
        });
    };
    return OccurrencesAdapter;
}(Adapter));

// --- definition ------
var DefinitionAdapter = /** @class */ (function (_super) {
    __extends(DefinitionAdapter, _super);
    function DefinitionAdapter() {
        return _super !== null && _super.apply(this, arguments) || this;
    }
    DefinitionAdapter.prototype.provideDefinition = function (model, position, token) {
        var _this = this;
        var resource = model.uri;
        return this._worker(resource).then(function (worker) {
            return worker.getDefinitionAtPosition(resource.toString(), _this._positionToOffset(resource, position));
        }).then(function (entries) {
            if (!entries) {
                return;
            }
            var result = [];
            for (var _i = 0, entries_1 = entries; _i < entries_1.length; _i++) {
                var entry = entries_1[_i];
                var uri = Uri.parse(entry.fileName);
                if (monaco.editor.getModel(uri)) {
                    result.push({
                        uri: uri,
                        range: _this._textSpanToRange(uri, entry.textSpan)
                    });
                }
            }
            return result;
        });
    };
    return DefinitionAdapter;
}(Adapter));

// --- references ------
var ReferenceAdapter = /** @class */ (function (_super) {
    __extends(ReferenceAdapter, _super);
    function ReferenceAdapter() {
        return _super !== null && _super.apply(this, arguments) || this;
    }
    ReferenceAdapter.prototype.provideReferences = function (model, position, context, token) {
        var _this = this;
        var resource = model.uri;
        return this._worker(resource).then(function (worker) {
            return worker.getReferencesAtPosition(resource.toString(), _this._positionToOffset(resource, position));
        }).then(function (entries) {
            if (!entries) {
                return;
            }
            var result = [];
            for (var _i = 0, entries_2 = entries; _i < entries_2.length; _i++) {
                var entry = entries_2[_i];
                var uri = Uri.parse(entry.fileName);
                if (monaco.editor.getModel(uri)) {
                    result.push({
                        uri: uri,
                        range: _this._textSpanToRange(uri, entry.textSpan)
                    });
                }
            }
            return result;
        });
    };
    return ReferenceAdapter;
}(Adapter));

// --- outline ------
var OutlineAdapter = /** @class */ (function (_super) {
    __extends(OutlineAdapter, _super);
    function OutlineAdapter() {
        return _super !== null && _super.apply(this, arguments) || this;
    }
    OutlineAdapter.prototype.provideDocumentSymbols = function (model, token) {
        var _this = this;
        var resource = model.uri;
        return this._worker(resource).then(function (worker) { return worker.getNavigationBarItems(resource.toString()); }).then(function (items) {
            if (!items) {
                return;
            }
            var convert = function (bucket, item, containerLabel) {
                var result = {
                    name: item.text,
                    detail: '',
                    kind: (outlineTypeTable[item.kind] || monaco.languages.SymbolKind.Variable),
                    range: _this._textSpanToRange(resource, item.spans[0]),
                    selectionRange: _this._textSpanToRange(resource, item.spans[0]),
                    containerName: containerLabel
                };
                if (item.childItems && item.childItems.length > 0) {
                    for (var _i = 0, _a = item.childItems; _i < _a.length; _i++) {
                        var child = _a[_i];
                        convert(bucket, child, result.name);
                    }
                }
                bucket.push(result);
            };
            var result = [];
            items.forEach(function (item) { return convert(result, item); });
            return result;
        });
    };
    return OutlineAdapter;
}(Adapter));

var Kind = /** @class */ (function () {
    function Kind() {
    }
    Kind.unknown = '';
    Kind.keyword = 'keyword';
    Kind.script = 'script';
    Kind.module = 'module';
    Kind.class = 'class';
    Kind.interface = 'interface';
    Kind.type = 'type';
    Kind.enum = 'enum';
    Kind.variable = 'var';
    Kind.localVariable = 'local var';
    Kind.function = 'function';
    Kind.localFunction = 'local function';
    Kind.memberFunction = 'method';
    Kind.memberGetAccessor = 'getter';
    Kind.memberSetAccessor = 'setter';
    Kind.memberVariable = 'property';
    Kind.constructorImplementation = 'constructor';
    Kind.callSignature = 'call';
    Kind.indexSignature = 'index';
    Kind.constructSignature = 'construct';
    Kind.parameter = 'parameter';
    Kind.typeParameter = 'type parameter';
    Kind.primitiveType = 'primitive type';
    Kind.label = 'label';
    Kind.alias = 'alias';
    Kind.const = 'const';
    Kind.let = 'let';
    Kind.warning = 'warning';
    return Kind;
}());

var outlineTypeTable = Object.create(null);
outlineTypeTable[Kind.module] = monaco.languages.SymbolKind.Module;
outlineTypeTable[Kind.class] = monaco.languages.SymbolKind.Class;
outlineTypeTable[Kind.enum] = monaco.languages.SymbolKind.Enum;
outlineTypeTable[Kind.interface] = monaco.languages.SymbolKind.Interface;
outlineTypeTable[Kind.memberFunction] = monaco.languages.SymbolKind.Method;
outlineTypeTable[Kind.memberVariable] = monaco.languages.SymbolKind.Property;
outlineTypeTable[Kind.memberGetAccessor] = monaco.languages.SymbolKind.Property;
outlineTypeTable[Kind.memberSetAccessor] = monaco.languages.SymbolKind.Property;
outlineTypeTable[Kind.variable] = monaco.languages.SymbolKind.Variable;
outlineTypeTable[Kind.const] = monaco.languages.SymbolKind.Variable;
outlineTypeTable[Kind.localVariable] = monaco.languages.SymbolKind.Variable;
outlineTypeTable[Kind.variable] = monaco.languages.SymbolKind.Variable;
outlineTypeTable[Kind.function] = monaco.languages.SymbolKind.Function;
outlineTypeTable[Kind.localFunction] = monaco.languages.SymbolKind.Function;
// --- formatting ----
var FormatHelper = /** @class */ (function (_super) {
    __extends(FormatHelper, _super);
    function FormatHelper() {
        return _super !== null && _super.apply(this, arguments) || this;
    }
    FormatHelper._convertOptions = function (options) {
        return {
            ConvertTabsToSpaces: options.insertSpaces,
            TabSize: options.tabSize,
            IndentSize: options.tabSize,
            IndentStyle: IndentStyle.Smart,
            NewLineCharacter: '\n',
            InsertSpaceAfterCommaDelimiter: true,
            InsertSpaceAfterSemicolonInForStatements: true,
            InsertSpaceBeforeAndAfterBinaryOperators: true,
            InsertSpaceAfterKeywordsInControlFlowStatements: true,
            InsertSpaceAfterFunctionKeywordForAnonymousFunctions: true,
            InsertSpaceAfterOpeningAndBeforeClosingNonemptyParenthesis: false,
            InsertSpaceAfterOpeningAndBeforeClosingNonemptyBrackets: false,
            InsertSpaceAfterOpeningAndBeforeClosingTemplateStringBraces: false,
            PlaceOpenBraceOnNewLineForControlBlocks: false,
            PlaceOpenBraceOnNewLineForFunctions: false
        };
    };
    FormatHelper.prototype._convertTextChanges = function (uri, change) {
        return {
            text: change.newText,
            range: this._textSpanToRange(uri, change.span)
        };
    };
    return FormatHelper;
}(Adapter));

var FormatAdapter = /** @class */ (function (_super) {
    __extends(FormatAdapter, _super);
    function FormatAdapter() {
        return _super !== null && _super.apply(this, arguments) || this;
    }
    FormatAdapter.prototype.provideDocumentRangeFormattingEdits = function (model, range, options, token) {
        var _this = this;
        var resource = model.uri;
        return this._worker(resource).then(function (worker) {
            return worker.getFormattingEditsForRange(resource.toString(), _this._positionToOffset(resource, { lineNumber: range.startLineNumber, column: range.startColumn }), _this._positionToOffset(resource, { lineNumber: range.endLineNumber, column: range.endColumn }), FormatHelper._convertOptions(options));
        }).then(function (edits) {
            if (edits) {
                return edits.map(function (edit) { return _this._convertTextChanges(resource, edit); });
            }
        });
    };
    return FormatAdapter;
}(FormatHelper));

var FormatOnTypeAdapter = /** @class */ (function (_super) {
    __extends(FormatOnTypeAdapter, _super);
    function FormatOnTypeAdapter() {
        return _super !== null && _super.apply(this, arguments) || this;
    }
    Object.defineProperty(FormatOnTypeAdapter.prototype, "autoFormatTriggerCharacters", {
        get: function () {
            return [';', '}', '\n'];
        },
        enumerable: true,
        configurable: true
    });
    FormatOnTypeAdapter.prototype.provideOnTypeFormattingEdits = function (model, position, ch, options, token) {
        var _this = this;
        var resource = model.uri;
        return this._worker(resource).then(function (worker) {
            return worker.getFormattingEditsAfterKeystroke(resource.toString(), _this._positionToOffset(resource, position), ch, FormatHelper._convertOptions(options));
        }).then(function (edits) {
            if (edits) {
                return edits.map(function (edit) { return _this._convertTextChanges(resource, edit); });
            }
        });
    };
    return FormatOnTypeAdapter;
}(FormatHelper));



/***/ }),

/***/ "./node_modules/monaco-editor/esm/vs/language/typescript/tsMode.js":
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
Object.defineProperty(__webpack_exports__, "__esModule", { value: true });
/* harmony export (immutable) */ __webpack_exports__["setupTypeScript"] = setupTypeScript;
/* harmony export (immutable) */ __webpack_exports__["setupJavaScript"] = setupJavaScript;
/* harmony export (immutable) */ __webpack_exports__["getJavaScriptWorker"] = getJavaScriptWorker;
/* harmony export (immutable) */ __webpack_exports__["getTypeScriptWorker"] = getTypeScriptWorker;
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_0__workerManager_js__ = __webpack_require__("./node_modules/monaco-editor/esm/vs/language/typescript/workerManager.js");
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_1__languageFeatures_js__ = __webpack_require__("./node_modules/monaco-editor/esm/vs/language/typescript/languageFeatures.js");
/*---------------------------------------------------------------------------------------------
 *  Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/



var javaScriptWorker;
var typeScriptWorker;
function setupTypeScript(defaults) {
    typeScriptWorker = setupMode(defaults, 'typescript');
}
function setupJavaScript(defaults) {
    javaScriptWorker = setupMode(defaults, 'javascript');
}
function getJavaScriptWorker() {
    return new monaco.Promise(function (resolve, reject) {
        if (!javaScriptWorker) {
            return reject("JavaScript not registered!");
        }
        resolve(javaScriptWorker);
    });
}
function getTypeScriptWorker() {
    return new monaco.Promise(function (resolve, reject) {
        if (!typeScriptWorker) {
            return reject("TypeScript not registered!");
        }
        resolve(typeScriptWorker);
    });
}
function setupMode(defaults, modeId) {
    var client = new __WEBPACK_IMPORTED_MODULE_0__workerManager_js__["a" /* WorkerManager */](modeId, defaults);
    var worker = function (first) {
        var more = [];
        for (var _i = 1; _i < arguments.length; _i++) {
            more[_i - 1] = arguments[_i];
        }
        return client.getLanguageServiceWorker.apply(client, [first].concat(more));
    };
    monaco.languages.registerCompletionItemProvider(modeId, new __WEBPACK_IMPORTED_MODULE_1__languageFeatures_js__["j" /* SuggestAdapter */](worker));
    monaco.languages.registerSignatureHelpProvider(modeId, new __WEBPACK_IMPORTED_MODULE_1__languageFeatures_js__["i" /* SignatureHelpAdapter */](worker));
    monaco.languages.registerHoverProvider(modeId, new __WEBPACK_IMPORTED_MODULE_1__languageFeatures_js__["g" /* QuickInfoAdapter */](worker));
    monaco.languages.registerDocumentHighlightProvider(modeId, new __WEBPACK_IMPORTED_MODULE_1__languageFeatures_js__["e" /* OccurrencesAdapter */](worker));
    monaco.languages.registerDefinitionProvider(modeId, new __WEBPACK_IMPORTED_MODULE_1__languageFeatures_js__["a" /* DefinitionAdapter */](worker));
    monaco.languages.registerReferenceProvider(modeId, new __WEBPACK_IMPORTED_MODULE_1__languageFeatures_js__["h" /* ReferenceAdapter */](worker));
    monaco.languages.registerDocumentSymbolProvider(modeId, new __WEBPACK_IMPORTED_MODULE_1__languageFeatures_js__["f" /* OutlineAdapter */](worker));
    monaco.languages.registerDocumentRangeFormattingEditProvider(modeId, new __WEBPACK_IMPORTED_MODULE_1__languageFeatures_js__["c" /* FormatAdapter */](worker));
    monaco.languages.registerOnTypeFormattingEditProvider(modeId, new __WEBPACK_IMPORTED_MODULE_1__languageFeatures_js__["d" /* FormatOnTypeAdapter */](worker));
    new __WEBPACK_IMPORTED_MODULE_1__languageFeatures_js__["b" /* DiagnostcsAdapter */](defaults, modeId, worker);
    return worker;
}


/***/ }),

/***/ "./node_modules/monaco-editor/esm/vs/language/typescript/workerManager.js":
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return WorkerManager; });
/*---------------------------------------------------------------------------------------------
 *  Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

var WorkerManager = /** @class */ (function () {
    function WorkerManager(modeId, defaults) {
        var _this = this;
        this._modeId = modeId;
        this._defaults = defaults;
        this._worker = null;
        this._idleCheckInterval = setInterval(function () { return _this._checkIfIdle(); }, 30 * 1000);
        this._lastUsedTime = 0;
        this._configChangeListener = this._defaults.onDidChange(function () { return _this._stopWorker(); });
    }
    WorkerManager.prototype._stopWorker = function () {
        if (this._worker) {
            this._worker.dispose();
            this._worker = null;
        }
        this._client = null;
    };
    WorkerManager.prototype.dispose = function () {
        clearInterval(this._idleCheckInterval);
        this._configChangeListener.dispose();
        this._stopWorker();
    };
    WorkerManager.prototype._checkIfIdle = function () {
        if (!this._worker) {
            return;
        }
        var maxIdleTime = this._defaults.getWorkerMaxIdleTime();
        var timePassedSinceLastUsed = Date.now() - this._lastUsedTime;
        if (maxIdleTime > 0 && timePassedSinceLastUsed > maxIdleTime) {
            this._stopWorker();
        }
    };
    WorkerManager.prototype._getClient = function () {
        var _this = this;
        this._lastUsedTime = Date.now();
        if (!this._client) {
            this._worker = monaco.editor.createWebWorker({
                // module that exports the create() method and returns a `TypeScriptWorker` instance
                moduleId: 'vs/language/typescript/tsWorker',
                label: this._modeId,
                // passed in to the create() method
                createData: {
                    compilerOptions: this._defaults.getCompilerOptions(),
                    extraLibs: this._defaults.getExtraLibs()
                }
            });
            var p = this._worker.getProxy();
            if (this._defaults.getEagerModelSync()) {
                p = p.then(function (worker) {
                    return _this._worker.withSyncedResources(monaco.editor.getModels()
                        .filter(function (model) { return model.getModeId() === _this._modeId; })
                        .map(function (model) { return model.uri; }));
                });
            }
            this._client = p;
        }
        return this._client;
    };
    WorkerManager.prototype.getLanguageServiceWorker = function () {
        var _this = this;
        var resources = [];
        for (var _i = 0; _i < arguments.length; _i++) {
            resources[_i] = arguments[_i];
        }
        var _client;
        return this._getClient().then(function (client) {
            _client = client;
        }).then(function (_) {
            return _this._worker.withSyncedResources(resources);
        }).then(function (_) { return _client; });
    };
    return WorkerManager;
}());



/***/ })

});