<?php

namespace PHPMaker2021\simexamerica;

use Doctrine\DBAL\ParameterType;

/**
 * Page class
 */
class ArrowchatApplicationsAdd extends ArrowchatApplications
{
    use MessagesTrait;

    // Page ID
    public $PageID = "add";

    // Project ID
    public $ProjectID = PROJECT_ID;

    // Table name
    public $TableName = 'arrowchat_applications';

    // Page object name
    public $PageObjName = "ArrowchatApplicationsAdd";

    // Rendering View
    public $RenderingView = false;

    // Page headings
    public $Heading = "";
    public $Subheading = "";
    public $PageHeader;
    public $PageFooter;

    // Page terminated
    private $terminated = false;

    // Page heading
    public function pageHeading()
    {
        global $Language;
        if ($this->Heading != "") {
            return $this->Heading;
        }
        if (method_exists($this, "tableCaption")) {
            return $this->tableCaption();
        }
        return "";
    }

    // Page subheading
    public function pageSubheading()
    {
        global $Language;
        if ($this->Subheading != "") {
            return $this->Subheading;
        }
        if ($this->TableName) {
            return $Language->phrase($this->PageID);
        }
        return "";
    }

    // Page name
    public function pageName()
    {
        return CurrentPageName();
    }

    // Page URL
    public function pageUrl()
    {
        $url = ScriptName() . "?";
        if ($this->UseTokenInUrl) {
            $url .= "t=" . $this->TableVar . "&"; // Add page token
        }
        return $url;
    }

    // Show Page Header
    public function showPageHeader()
    {
        $header = $this->PageHeader;
        $this->pageDataRendering($header);
        if ($header != "") { // Header exists, display
            echo '<p id="ew-page-header">' . $header . '</p>';
        }
    }

    // Show Page Footer
    public function showPageFooter()
    {
        $footer = $this->PageFooter;
        $this->pageDataRendered($footer);
        if ($footer != "") { // Footer exists, display
            echo '<p id="ew-page-footer">' . $footer . '</p>';
        }
    }

    // Validate page request
    protected function isPageRequest()
    {
        global $CurrentForm;
        if ($this->UseTokenInUrl) {
            if ($CurrentForm) {
                return ($this->TableVar == $CurrentForm->getValue("t"));
            }
            if (Get("t") !== null) {
                return ($this->TableVar == Get("t"));
            }
        }
        return true;
    }

    // Constructor
    public function __construct()
    {
        global $Language, $DashboardReport, $DebugTimer;
        global $UserTable;

        // Initialize
        $GLOBALS["Page"] = &$this;

        // Language object
        $Language = Container("language");

        // Parent constuctor
        parent::__construct();

        // Table object (arrowchat_applications)
        if (!isset($GLOBALS["arrowchat_applications"]) || get_class($GLOBALS["arrowchat_applications"]) == PROJECT_NAMESPACE . "arrowchat_applications") {
            $GLOBALS["arrowchat_applications"] = &$this;
        }

        // Page URL
        $pageUrl = $this->pageUrl();

        // Table name (for backward compatibility only)
        if (!defined(PROJECT_NAMESPACE . "TABLE_NAME")) {
            define(PROJECT_NAMESPACE . "TABLE_NAME", 'arrowchat_applications');
        }

        // Start timer
        $DebugTimer = Container("timer");

        // Debug message
        LoadDebugMessage();

        // Open connection
        $GLOBALS["Conn"] = $GLOBALS["Conn"] ?? $this->getConnection();

        // User table object
        $UserTable = Container("usertable");
    }

    // Get content from stream
    public function getContents($stream = null): string
    {
        global $Response;
        return is_object($Response) ? $Response->getBody() : ob_get_clean();
    }

    // Is lookup
    public function isLookup()
    {
        return SameText(Route(0), Config("API_LOOKUP_ACTION"));
    }

    // Is AutoFill
    public function isAutoFill()
    {
        return $this->isLookup() && SameText(Post("ajax"), "autofill");
    }

    // Is AutoSuggest
    public function isAutoSuggest()
    {
        return $this->isLookup() && SameText(Post("ajax"), "autosuggest");
    }

    // Is modal lookup
    public function isModalLookup()
    {
        return $this->isLookup() && SameText(Post("ajax"), "modal");
    }

    // Is terminated
    public function isTerminated()
    {
        return $this->terminated;
    }

    /**
     * Terminate page
     *
     * @param string $url URL for direction
     * @return void
     */
    public function terminate($url = "")
    {
        if ($this->terminated) {
            return;
        }
        global $ExportFileName, $TempImages, $DashboardReport, $Response;

        // Page is terminated
        $this->terminated = true;

         // Page Unload event
        if (method_exists($this, "pageUnload")) {
            $this->pageUnload();
        }

        // Global Page Unloaded event (in userfn*.php)
        Page_Unloaded();

        // Export
        if ($this->CustomExport && $this->CustomExport == $this->Export && array_key_exists($this->CustomExport, Config("EXPORT_CLASSES"))) {
            $content = $this->getContents();
            if ($ExportFileName == "") {
                $ExportFileName = $this->TableVar;
            }
            $class = PROJECT_NAMESPACE . Config("EXPORT_CLASSES." . $this->CustomExport);
            if (class_exists($class)) {
                $doc = new $class(Container("arrowchat_applications"));
                $doc->Text = @$content;
                if ($this->isExport("email")) {
                    echo $this->exportEmail($doc->Text);
                } else {
                    $doc->export();
                }
                DeleteTempImages(); // Delete temp images
                return;
            }
        }
        if (!IsApi() && method_exists($this, "pageRedirecting")) {
            $this->pageRedirecting($url);
        }

        // Close connection
        CloseConnections();

        // Return for API
        if (IsApi()) {
            $res = $url === true;
            if (!$res) { // Show error
                WriteJson(array_merge(["success" => false], $this->getMessages()));
            }
            return;
        } else { // Check if response is JSON
            if (StartsString("application/json", $Response->getHeaderLine("Content-type")) && $Response->getBody()->getSize()) { // With JSON response
                $this->clearMessages();
                return;
            }
        }

        // Go to URL if specified
        if ($url != "") {
            if (!Config("DEBUG") && ob_get_length()) {
                ob_end_clean();
            }

            // Handle modal response
            if ($this->IsModal) { // Show as modal
                $row = ["url" => GetUrl($url), "modal" => "1"];
                $pageName = GetPageName($url);
                if ($pageName != $this->getListUrl()) { // Not List page
                    $row["caption"] = $this->getModalCaption($pageName);
                    if ($pageName == "ArrowchatApplicationsView") {
                        $row["view"] = "1";
                    }
                } else { // List page should not be shown as modal => error
                    $row["error"] = $this->getFailureMessage();
                    $this->clearFailureMessage();
                }
                WriteJson($row);
            } else {
                SaveDebugMessage();
                Redirect(GetUrl($url));
            }
        }
        return; // Return to controller
    }

    // Get records from recordset
    protected function getRecordsFromRecordset($rs, $current = false)
    {
        $rows = [];
        if (is_object($rs)) { // Recordset
            while ($rs && !$rs->EOF) {
                $this->loadRowValues($rs); // Set up DbValue/CurrentValue
                $row = $this->getRecordFromArray($rs->fields);
                if ($current) {
                    return $row;
                } else {
                    $rows[] = $row;
                }
                $rs->moveNext();
            }
        } elseif (is_array($rs)) {
            foreach ($rs as $ar) {
                $row = $this->getRecordFromArray($ar);
                if ($current) {
                    return $row;
                } else {
                    $rows[] = $row;
                }
            }
        }
        return $rows;
    }

    // Get record from array
    protected function getRecordFromArray($ar)
    {
        $row = [];
        if (is_array($ar)) {
            foreach ($ar as $fldname => $val) {
                if (array_key_exists($fldname, $this->Fields) && ($this->Fields[$fldname]->Visible || $this->Fields[$fldname]->IsPrimaryKey)) { // Primary key or Visible
                    $fld = &$this->Fields[$fldname];
                    if ($fld->HtmlTag == "FILE") { // Upload field
                        if (EmptyValue($val)) {
                            $row[$fldname] = null;
                        } else {
                            if ($fld->DataType == DATATYPE_BLOB) {
                                $url = FullUrl(GetApiUrl(Config("API_FILE_ACTION") .
                                    "/" . $fld->TableVar . "/" . $fld->Param . "/" . rawurlencode($this->getRecordKeyValue($ar))));
                                $row[$fldname] = ["type" => ContentType($val), "url" => $url, "name" => $fld->Param . ContentExtension($val)];
                            } elseif (!$fld->UploadMultiple || !ContainsString($val, Config("MULTIPLE_UPLOAD_SEPARATOR"))) { // Single file
                                $url = FullUrl(GetApiUrl(Config("API_FILE_ACTION") .
                                    "/" . $fld->TableVar . "/" . Encrypt($fld->physicalUploadPath() . $val)));
                                $row[$fldname] = ["type" => MimeContentType($val), "url" => $url, "name" => $val];
                            } else { // Multiple files
                                $files = explode(Config("MULTIPLE_UPLOAD_SEPARATOR"), $val);
                                $ar = [];
                                foreach ($files as $file) {
                                    $url = FullUrl(GetApiUrl(Config("API_FILE_ACTION") .
                                        "/" . $fld->TableVar . "/" . Encrypt($fld->physicalUploadPath() . $file)));
                                    if (!EmptyValue($file)) {
                                        $ar[] = ["type" => MimeContentType($file), "url" => $url, "name" => $file];
                                    }
                                }
                                $row[$fldname] = $ar;
                            }
                        }
                    } else {
                        $row[$fldname] = $val;
                    }
                }
            }
        }
        return $row;
    }

    // Get record key value from array
    protected function getRecordKeyValue($ar)
    {
        $key = "";
        if (is_array($ar)) {
            $key .= @$ar['id'];
        }
        return $key;
    }

    /**
     * Hide fields for add/edit
     *
     * @return void
     */
    protected function hideFieldsForAddEdit()
    {
        if ($this->isAdd() || $this->isCopy() || $this->isGridAdd()) {
            $this->id->Visible = false;
        }
    }

    // Lookup data
    public function lookup()
    {
        global $Language, $Security;

        // Get lookup object
        $fieldName = Post("field");
        $lookup = $this->Fields[$fieldName]->Lookup;

        // Get lookup parameters
        $lookupType = Post("ajax", "unknown");
        $pageSize = -1;
        $offset = -1;
        $searchValue = "";
        if (SameText($lookupType, "modal")) {
            $searchValue = Post("sv", "");
            $pageSize = Post("recperpage", 10);
            $offset = Post("start", 0);
        } elseif (SameText($lookupType, "autosuggest")) {
            $searchValue = Param("q", "");
            $pageSize = Param("n", -1);
            $pageSize = is_numeric($pageSize) ? (int)$pageSize : -1;
            if ($pageSize <= 0) {
                $pageSize = Config("AUTO_SUGGEST_MAX_ENTRIES");
            }
            $start = Param("start", -1);
            $start = is_numeric($start) ? (int)$start : -1;
            $page = Param("page", -1);
            $page = is_numeric($page) ? (int)$page : -1;
            $offset = $start >= 0 ? $start : ($page > 0 && $pageSize > 0 ? ($page - 1) * $pageSize : 0);
        }
        $userSelect = Decrypt(Post("s", ""));
        $userFilter = Decrypt(Post("f", ""));
        $userOrderBy = Decrypt(Post("o", ""));
        $keys = Post("keys");
        $lookup->LookupType = $lookupType; // Lookup type
        if ($keys !== null) { // Selected records from modal
            if (is_array($keys)) {
                $keys = implode(Config("MULTIPLE_OPTION_SEPARATOR"), $keys);
            }
            $lookup->FilterFields = []; // Skip parent fields if any
            $lookup->FilterValues[] = $keys; // Lookup values
            $pageSize = -1; // Show all records
        } else { // Lookup values
            $lookup->FilterValues[] = Post("v0", Post("lookupValue", ""));
        }
        $cnt = is_array($lookup->FilterFields) ? count($lookup->FilterFields) : 0;
        for ($i = 1; $i <= $cnt; $i++) {
            $lookup->FilterValues[] = Post("v" . $i, "");
        }
        $lookup->SearchValue = $searchValue;
        $lookup->PageSize = $pageSize;
        $lookup->Offset = $offset;
        if ($userSelect != "") {
            $lookup->UserSelect = $userSelect;
        }
        if ($userFilter != "") {
            $lookup->UserFilter = $userFilter;
        }
        if ($userOrderBy != "") {
            $lookup->UserOrderBy = $userOrderBy;
        }
        $lookup->toJson($this); // Use settings from current page
    }
    public $FormClassName = "ew-horizontal ew-form ew-add-form";
    public $IsModal = false;
    public $IsMobileOrModal = false;
    public $DbMasterFilter = "";
    public $DbDetailFilter = "";
    public $StartRecord;
    public $Priv = 0;
    public $OldRecordset;
    public $CopyRecord;

    /**
     * Page run
     *
     * @return void
     */
    public function run()
    {
        global $ExportType, $CustomExportType, $ExportFileName, $UserProfile, $Language, $Security, $CurrentForm,
            $SkipHeaderFooter;

        // Is modal
        $this->IsModal = Param("modal") == "1";

        // Create form object
        $CurrentForm = new HttpForm();
        $this->CurrentAction = Param("action"); // Set up current action
        $this->id->Visible = false;
        $this->name->setVisibility();
        $this->folder->setVisibility();
        $this->icon->setVisibility();
        $this->width->setVisibility();
        $this->height->setVisibility();
        $this->bar_width->setVisibility();
        $this->bar_name->setVisibility();
        $this->dont_reload->setVisibility();
        $this->default_bookmark->setVisibility();
        $this->show_to_guests->setVisibility();
        $this->link->setVisibility();
        $this->update_link->setVisibility();
        $this->version->setVisibility();
        $this->active->setVisibility();
        $this->hideFieldsForAddEdit();

        // Do not use lookup cache
        $this->setUseLookupCache(false);

        // Global Page Loading event (in userfn*.php)
        Page_Loading();

        // Page Load event
        if (method_exists($this, "pageLoad")) {
            $this->pageLoad();
        }

        // Set up lookup cache

        // Check modal
        if ($this->IsModal) {
            $SkipHeaderFooter = true;
        }
        $this->IsMobileOrModal = IsMobile() || $this->IsModal;
        $this->FormClassName = "ew-form ew-add-form ew-horizontal";
        $postBack = false;

        // Set up current action
        if (IsApi()) {
            $this->CurrentAction = "insert"; // Add record directly
            $postBack = true;
        } elseif (Post("action") !== null) {
            $this->CurrentAction = Post("action"); // Get form action
            $this->setKey(Post($this->OldKeyName));
            $postBack = true;
        } else {
            // Load key values from QueryString
            if (($keyValue = Get("id") ?? Route("id")) !== null) {
                $this->id->setQueryStringValue($keyValue);
            }
            $this->OldKey = $this->getKey(true); // Get from CurrentValue
            $this->CopyRecord = !EmptyValue($this->OldKey);
            if ($this->CopyRecord) {
                $this->CurrentAction = "copy"; // Copy record
            } else {
                $this->CurrentAction = "show"; // Display blank record
            }
        }

        // Load old record / default values
        $loaded = $this->loadOldRecord();

        // Load form values
        if ($postBack) {
            $this->loadFormValues(); // Load form values
        }

        // Validate form if post back
        if ($postBack) {
            if (!$this->validateForm()) {
                $this->EventCancelled = true; // Event cancelled
                $this->restoreFormValues(); // Restore form values
                if (IsApi()) {
                    $this->terminate();
                    return;
                } else {
                    $this->CurrentAction = "show"; // Form error, reset action
                }
            }
        }

        // Perform current action
        switch ($this->CurrentAction) {
            case "copy": // Copy an existing record
                if (!$loaded) { // Record not loaded
                    if ($this->getFailureMessage() == "") {
                        $this->setFailureMessage($Language->phrase("NoRecord")); // No record found
                    }
                    $this->terminate("ArrowchatApplicationsList"); // No matching record, return to list
                    return;
                }
                break;
            case "insert": // Add new record
                $this->SendEmail = true; // Send email on add success
                if ($this->addRow($this->OldRecordset)) { // Add successful
                    if ($this->getSuccessMessage() == "" && Post("addopt") != "1") { // Skip success message for addopt (done in JavaScript)
                        $this->setSuccessMessage($Language->phrase("AddSuccess")); // Set up success message
                    }
                    $returnUrl = $this->getReturnUrl();
                    if (GetPageName($returnUrl) == "ArrowchatApplicationsList") {
                        $returnUrl = $this->addMasterUrl($returnUrl); // List page, return to List page with correct master key if necessary
                    } elseif (GetPageName($returnUrl) == "ArrowchatApplicationsView") {
                        $returnUrl = $this->getViewUrl(); // View page, return to View page with keyurl directly
                    }
                    if (IsApi()) { // Return to caller
                        $this->terminate(true);
                        return;
                    } else {
                        $this->terminate($returnUrl);
                        return;
                    }
                } elseif (IsApi()) { // API request, return
                    $this->terminate();
                    return;
                } else {
                    $this->EventCancelled = true; // Event cancelled
                    $this->restoreFormValues(); // Add failed, restore form values
                }
        }

        // Set up Breadcrumb
        $this->setupBreadcrumb();

        // Render row based on row type
        $this->RowType = ROWTYPE_ADD; // Render add type

        // Render row
        $this->resetAttributes();
        $this->renderRow();

        // Set LoginStatus / Page_Rendering / Page_Render
        if (!IsApi() && !$this->isTerminated()) {
            // Pass table and field properties to client side
            $this->toClientVar(["tableCaption"], ["caption", "Visible", "Required", "IsInvalid", "Raw"]);

            // Setup login status
            SetupLoginStatus();

            // Pass login status to client side
            SetClientVar("login", LoginStatus());

            // Global Page Rendering event (in userfn*.php)
            Page_Rendering();

            // Page Render event
            if (method_exists($this, "pageRender")) {
                $this->pageRender();
            }
        }
    }

    // Get upload files
    protected function getUploadFiles()
    {
        global $CurrentForm, $Language;
    }

    // Load default values
    protected function loadDefaultValues()
    {
        $this->id->CurrentValue = null;
        $this->id->OldValue = $this->id->CurrentValue;
        $this->name->CurrentValue = null;
        $this->name->OldValue = $this->name->CurrentValue;
        $this->folder->CurrentValue = null;
        $this->folder->OldValue = $this->folder->CurrentValue;
        $this->icon->CurrentValue = null;
        $this->icon->OldValue = $this->icon->CurrentValue;
        $this->width->CurrentValue = null;
        $this->width->OldValue = $this->width->CurrentValue;
        $this->height->CurrentValue = null;
        $this->height->OldValue = $this->height->CurrentValue;
        $this->bar_width->CurrentValue = null;
        $this->bar_width->OldValue = $this->bar_width->CurrentValue;
        $this->bar_name->CurrentValue = null;
        $this->bar_name->OldValue = $this->bar_name->CurrentValue;
        $this->dont_reload->CurrentValue = 0;
        $this->default_bookmark->CurrentValue = 1;
        $this->show_to_guests->CurrentValue = 1;
        $this->link->CurrentValue = null;
        $this->link->OldValue = $this->link->CurrentValue;
        $this->update_link->CurrentValue = null;
        $this->update_link->OldValue = $this->update_link->CurrentValue;
        $this->version->CurrentValue = null;
        $this->version->OldValue = $this->version->CurrentValue;
        $this->active->CurrentValue = 1;
    }

    // Load form values
    protected function loadFormValues()
    {
        // Load from form
        global $CurrentForm;

        // Check field name 'name' first before field var 'x_name'
        $val = $CurrentForm->hasValue("name") ? $CurrentForm->getValue("name") : $CurrentForm->getValue("x_name");
        if (!$this->name->IsDetailKey) {
            if (IsApi() && $val === null) {
                $this->name->Visible = false; // Disable update for API request
            } else {
                $this->name->setFormValue($val);
            }
        }

        // Check field name 'folder' first before field var 'x_folder'
        $val = $CurrentForm->hasValue("folder") ? $CurrentForm->getValue("folder") : $CurrentForm->getValue("x_folder");
        if (!$this->folder->IsDetailKey) {
            if (IsApi() && $val === null) {
                $this->folder->Visible = false; // Disable update for API request
            } else {
                $this->folder->setFormValue($val);
            }
        }

        // Check field name 'icon' first before field var 'x_icon'
        $val = $CurrentForm->hasValue("icon") ? $CurrentForm->getValue("icon") : $CurrentForm->getValue("x_icon");
        if (!$this->icon->IsDetailKey) {
            if (IsApi() && $val === null) {
                $this->icon->Visible = false; // Disable update for API request
            } else {
                $this->icon->setFormValue($val);
            }
        }

        // Check field name 'width' first before field var 'x_width'
        $val = $CurrentForm->hasValue("width") ? $CurrentForm->getValue("width") : $CurrentForm->getValue("x_width");
        if (!$this->width->IsDetailKey) {
            if (IsApi() && $val === null) {
                $this->width->Visible = false; // Disable update for API request
            } else {
                $this->width->setFormValue($val);
            }
        }

        // Check field name 'height' first before field var 'x_height'
        $val = $CurrentForm->hasValue("height") ? $CurrentForm->getValue("height") : $CurrentForm->getValue("x_height");
        if (!$this->height->IsDetailKey) {
            if (IsApi() && $val === null) {
                $this->height->Visible = false; // Disable update for API request
            } else {
                $this->height->setFormValue($val);
            }
        }

        // Check field name 'bar_width' first before field var 'x_bar_width'
        $val = $CurrentForm->hasValue("bar_width") ? $CurrentForm->getValue("bar_width") : $CurrentForm->getValue("x_bar_width");
        if (!$this->bar_width->IsDetailKey) {
            if (IsApi() && $val === null) {
                $this->bar_width->Visible = false; // Disable update for API request
            } else {
                $this->bar_width->setFormValue($val);
            }
        }

        // Check field name 'bar_name' first before field var 'x_bar_name'
        $val = $CurrentForm->hasValue("bar_name") ? $CurrentForm->getValue("bar_name") : $CurrentForm->getValue("x_bar_name");
        if (!$this->bar_name->IsDetailKey) {
            if (IsApi() && $val === null) {
                $this->bar_name->Visible = false; // Disable update for API request
            } else {
                $this->bar_name->setFormValue($val);
            }
        }

        // Check field name 'dont_reload' first before field var 'x_dont_reload'
        $val = $CurrentForm->hasValue("dont_reload") ? $CurrentForm->getValue("dont_reload") : $CurrentForm->getValue("x_dont_reload");
        if (!$this->dont_reload->IsDetailKey) {
            if (IsApi() && $val === null) {
                $this->dont_reload->Visible = false; // Disable update for API request
            } else {
                $this->dont_reload->setFormValue($val);
            }
        }

        // Check field name 'default_bookmark' first before field var 'x_default_bookmark'
        $val = $CurrentForm->hasValue("default_bookmark") ? $CurrentForm->getValue("default_bookmark") : $CurrentForm->getValue("x_default_bookmark");
        if (!$this->default_bookmark->IsDetailKey) {
            if (IsApi() && $val === null) {
                $this->default_bookmark->Visible = false; // Disable update for API request
            } else {
                $this->default_bookmark->setFormValue($val);
            }
        }

        // Check field name 'show_to_guests' first before field var 'x_show_to_guests'
        $val = $CurrentForm->hasValue("show_to_guests") ? $CurrentForm->getValue("show_to_guests") : $CurrentForm->getValue("x_show_to_guests");
        if (!$this->show_to_guests->IsDetailKey) {
            if (IsApi() && $val === null) {
                $this->show_to_guests->Visible = false; // Disable update for API request
            } else {
                $this->show_to_guests->setFormValue($val);
            }
        }

        // Check field name 'link' first before field var 'x_link'
        $val = $CurrentForm->hasValue("link") ? $CurrentForm->getValue("link") : $CurrentForm->getValue("x_link");
        if (!$this->link->IsDetailKey) {
            if (IsApi() && $val === null) {
                $this->link->Visible = false; // Disable update for API request
            } else {
                $this->link->setFormValue($val);
            }
        }

        // Check field name 'update_link' first before field var 'x_update_link'
        $val = $CurrentForm->hasValue("update_link") ? $CurrentForm->getValue("update_link") : $CurrentForm->getValue("x_update_link");
        if (!$this->update_link->IsDetailKey) {
            if (IsApi() && $val === null) {
                $this->update_link->Visible = false; // Disable update for API request
            } else {
                $this->update_link->setFormValue($val);
            }
        }

        // Check field name 'version' first before field var 'x_version'
        $val = $CurrentForm->hasValue("version") ? $CurrentForm->getValue("version") : $CurrentForm->getValue("x_version");
        if (!$this->version->IsDetailKey) {
            if (IsApi() && $val === null) {
                $this->version->Visible = false; // Disable update for API request
            } else {
                $this->version->setFormValue($val);
            }
        }

        // Check field name 'active' first before field var 'x_active'
        $val = $CurrentForm->hasValue("active") ? $CurrentForm->getValue("active") : $CurrentForm->getValue("x_active");
        if (!$this->active->IsDetailKey) {
            if (IsApi() && $val === null) {
                $this->active->Visible = false; // Disable update for API request
            } else {
                $this->active->setFormValue($val);
            }
        }

        // Check field name 'id' first before field var 'x_id'
        $val = $CurrentForm->hasValue("id") ? $CurrentForm->getValue("id") : $CurrentForm->getValue("x_id");
    }

    // Restore form values
    public function restoreFormValues()
    {
        global $CurrentForm;
        $this->name->CurrentValue = $this->name->FormValue;
        $this->folder->CurrentValue = $this->folder->FormValue;
        $this->icon->CurrentValue = $this->icon->FormValue;
        $this->width->CurrentValue = $this->width->FormValue;
        $this->height->CurrentValue = $this->height->FormValue;
        $this->bar_width->CurrentValue = $this->bar_width->FormValue;
        $this->bar_name->CurrentValue = $this->bar_name->FormValue;
        $this->dont_reload->CurrentValue = $this->dont_reload->FormValue;
        $this->default_bookmark->CurrentValue = $this->default_bookmark->FormValue;
        $this->show_to_guests->CurrentValue = $this->show_to_guests->FormValue;
        $this->link->CurrentValue = $this->link->FormValue;
        $this->update_link->CurrentValue = $this->update_link->FormValue;
        $this->version->CurrentValue = $this->version->FormValue;
        $this->active->CurrentValue = $this->active->FormValue;
    }

    /**
     * Load row based on key values
     *
     * @return void
     */
    public function loadRow()
    {
        global $Security, $Language;
        $filter = $this->getRecordFilter();

        // Call Row Selecting event
        $this->rowSelecting($filter);

        // Load SQL based on filter
        $this->CurrentFilter = $filter;
        $sql = $this->getCurrentSql();
        $conn = $this->getConnection();
        $res = false;
        $row = $conn->fetchAssoc($sql);
        if ($row) {
            $res = true;
            $this->loadRowValues($row); // Load row values
        }
        return $res;
    }

    /**
     * Load row values from recordset or record
     *
     * @param Recordset|array $rs Record
     * @return void
     */
    public function loadRowValues($rs = null)
    {
        if (is_array($rs)) {
            $row = $rs;
        } elseif ($rs && property_exists($rs, "fields")) { // Recordset
            $row = $rs->fields;
        } else {
            $row = $this->newRow();
        }

        // Call Row Selected event
        $this->rowSelected($row);
        if (!$rs) {
            return;
        }
        $this->id->setDbValue($row['id']);
        $this->name->setDbValue($row['name']);
        $this->folder->setDbValue($row['folder']);
        $this->icon->setDbValue($row['icon']);
        $this->width->setDbValue($row['width']);
        $this->height->setDbValue($row['height']);
        $this->bar_width->setDbValue($row['bar_width']);
        $this->bar_name->setDbValue($row['bar_name']);
        $this->dont_reload->setDbValue($row['dont_reload']);
        $this->default_bookmark->setDbValue($row['default_bookmark']);
        $this->show_to_guests->setDbValue($row['show_to_guests']);
        $this->link->setDbValue($row['link']);
        $this->update_link->setDbValue($row['update_link']);
        $this->version->setDbValue($row['version']);
        $this->active->setDbValue($row['active']);
    }

    // Return a row with default values
    protected function newRow()
    {
        $this->loadDefaultValues();
        $row = [];
        $row['id'] = $this->id->CurrentValue;
        $row['name'] = $this->name->CurrentValue;
        $row['folder'] = $this->folder->CurrentValue;
        $row['icon'] = $this->icon->CurrentValue;
        $row['width'] = $this->width->CurrentValue;
        $row['height'] = $this->height->CurrentValue;
        $row['bar_width'] = $this->bar_width->CurrentValue;
        $row['bar_name'] = $this->bar_name->CurrentValue;
        $row['dont_reload'] = $this->dont_reload->CurrentValue;
        $row['default_bookmark'] = $this->default_bookmark->CurrentValue;
        $row['show_to_guests'] = $this->show_to_guests->CurrentValue;
        $row['link'] = $this->link->CurrentValue;
        $row['update_link'] = $this->update_link->CurrentValue;
        $row['version'] = $this->version->CurrentValue;
        $row['active'] = $this->active->CurrentValue;
        return $row;
    }

    // Load old record
    protected function loadOldRecord()
    {
        // Load old record
        $this->OldRecordset = null;
        $validKey = $this->OldKey != "";
        if ($validKey) {
            $this->CurrentFilter = $this->getRecordFilter();
            $sql = $this->getCurrentSql();
            $conn = $this->getConnection();
            $this->OldRecordset = LoadRecordset($sql, $conn);
        }
        $this->loadRowValues($this->OldRecordset); // Load row values
        return $validKey;
    }

    // Render row values based on field settings
    public function renderRow()
    {
        global $Security, $Language, $CurrentLanguage;

        // Initialize URLs

        // Call Row_Rendering event
        $this->rowRendering();

        // Common render codes for all row types

        // id

        // name

        // folder

        // icon

        // width

        // height

        // bar_width

        // bar_name

        // dont_reload

        // default_bookmark

        // show_to_guests

        // link

        // update_link

        // version

        // active
        if ($this->RowType == ROWTYPE_VIEW) {
            // id
            $this->id->ViewValue = $this->id->CurrentValue;
            $this->id->ViewCustomAttributes = "";

            // name
            $this->name->ViewValue = $this->name->CurrentValue;
            $this->name->ViewCustomAttributes = "";

            // folder
            $this->folder->ViewValue = $this->folder->CurrentValue;
            $this->folder->ViewCustomAttributes = "";

            // icon
            $this->icon->ViewValue = $this->icon->CurrentValue;
            $this->icon->ViewCustomAttributes = "";

            // width
            $this->width->ViewValue = $this->width->CurrentValue;
            $this->width->ViewValue = FormatNumber($this->width->ViewValue, 0, -2, -2, -2);
            $this->width->ViewCustomAttributes = "";

            // height
            $this->height->ViewValue = $this->height->CurrentValue;
            $this->height->ViewValue = FormatNumber($this->height->ViewValue, 0, -2, -2, -2);
            $this->height->ViewCustomAttributes = "";

            // bar_width
            $this->bar_width->ViewValue = $this->bar_width->CurrentValue;
            $this->bar_width->ViewValue = FormatNumber($this->bar_width->ViewValue, 0, -2, -2, -2);
            $this->bar_width->ViewCustomAttributes = "";

            // bar_name
            $this->bar_name->ViewValue = $this->bar_name->CurrentValue;
            $this->bar_name->ViewCustomAttributes = "";

            // dont_reload
            if (ConvertToBool($this->dont_reload->CurrentValue)) {
                $this->dont_reload->ViewValue = $this->dont_reload->tagCaption(1) != "" ? $this->dont_reload->tagCaption(1) : "Yes";
            } else {
                $this->dont_reload->ViewValue = $this->dont_reload->tagCaption(2) != "" ? $this->dont_reload->tagCaption(2) : "No";
            }
            $this->dont_reload->ViewCustomAttributes = "";

            // default_bookmark
            if (ConvertToBool($this->default_bookmark->CurrentValue)) {
                $this->default_bookmark->ViewValue = $this->default_bookmark->tagCaption(1) != "" ? $this->default_bookmark->tagCaption(1) : "Yes";
            } else {
                $this->default_bookmark->ViewValue = $this->default_bookmark->tagCaption(2) != "" ? $this->default_bookmark->tagCaption(2) : "No";
            }
            $this->default_bookmark->ViewCustomAttributes = "";

            // show_to_guests
            if (ConvertToBool($this->show_to_guests->CurrentValue)) {
                $this->show_to_guests->ViewValue = $this->show_to_guests->tagCaption(1) != "" ? $this->show_to_guests->tagCaption(1) : "Yes";
            } else {
                $this->show_to_guests->ViewValue = $this->show_to_guests->tagCaption(2) != "" ? $this->show_to_guests->tagCaption(2) : "No";
            }
            $this->show_to_guests->ViewCustomAttributes = "";

            // link
            $this->link->ViewValue = $this->link->CurrentValue;
            $this->link->ViewCustomAttributes = "";

            // update_link
            $this->update_link->ViewValue = $this->update_link->CurrentValue;
            $this->update_link->ViewCustomAttributes = "";

            // version
            $this->version->ViewValue = $this->version->CurrentValue;
            $this->version->ViewCustomAttributes = "";

            // active
            if (ConvertToBool($this->active->CurrentValue)) {
                $this->active->ViewValue = $this->active->tagCaption(1) != "" ? $this->active->tagCaption(1) : "Yes";
            } else {
                $this->active->ViewValue = $this->active->tagCaption(2) != "" ? $this->active->tagCaption(2) : "No";
            }
            $this->active->ViewCustomAttributes = "";

            // name
            $this->name->LinkCustomAttributes = "";
            $this->name->HrefValue = "";
            $this->name->TooltipValue = "";

            // folder
            $this->folder->LinkCustomAttributes = "";
            $this->folder->HrefValue = "";
            $this->folder->TooltipValue = "";

            // icon
            $this->icon->LinkCustomAttributes = "";
            $this->icon->HrefValue = "";
            $this->icon->TooltipValue = "";

            // width
            $this->width->LinkCustomAttributes = "";
            $this->width->HrefValue = "";
            $this->width->TooltipValue = "";

            // height
            $this->height->LinkCustomAttributes = "";
            $this->height->HrefValue = "";
            $this->height->TooltipValue = "";

            // bar_width
            $this->bar_width->LinkCustomAttributes = "";
            $this->bar_width->HrefValue = "";
            $this->bar_width->TooltipValue = "";

            // bar_name
            $this->bar_name->LinkCustomAttributes = "";
            $this->bar_name->HrefValue = "";
            $this->bar_name->TooltipValue = "";

            // dont_reload
            $this->dont_reload->LinkCustomAttributes = "";
            $this->dont_reload->HrefValue = "";
            $this->dont_reload->TooltipValue = "";

            // default_bookmark
            $this->default_bookmark->LinkCustomAttributes = "";
            $this->default_bookmark->HrefValue = "";
            $this->default_bookmark->TooltipValue = "";

            // show_to_guests
            $this->show_to_guests->LinkCustomAttributes = "";
            $this->show_to_guests->HrefValue = "";
            $this->show_to_guests->TooltipValue = "";

            // link
            $this->link->LinkCustomAttributes = "";
            $this->link->HrefValue = "";
            $this->link->TooltipValue = "";

            // update_link
            $this->update_link->LinkCustomAttributes = "";
            $this->update_link->HrefValue = "";
            $this->update_link->TooltipValue = "";

            // version
            $this->version->LinkCustomAttributes = "";
            $this->version->HrefValue = "";
            $this->version->TooltipValue = "";

            // active
            $this->active->LinkCustomAttributes = "";
            $this->active->HrefValue = "";
            $this->active->TooltipValue = "";
        } elseif ($this->RowType == ROWTYPE_ADD) {
            // name
            $this->name->EditAttrs["class"] = "form-control";
            $this->name->EditCustomAttributes = "";
            if (!$this->name->Raw) {
                $this->name->CurrentValue = HtmlDecode($this->name->CurrentValue);
            }
            $this->name->EditValue = HtmlEncode($this->name->CurrentValue);
            $this->name->PlaceHolder = RemoveHtml($this->name->caption());

            // folder
            $this->folder->EditAttrs["class"] = "form-control";
            $this->folder->EditCustomAttributes = "";
            if (!$this->folder->Raw) {
                $this->folder->CurrentValue = HtmlDecode($this->folder->CurrentValue);
            }
            $this->folder->EditValue = HtmlEncode($this->folder->CurrentValue);
            $this->folder->PlaceHolder = RemoveHtml($this->folder->caption());

            // icon
            $this->icon->EditAttrs["class"] = "form-control";
            $this->icon->EditCustomAttributes = "";
            if (!$this->icon->Raw) {
                $this->icon->CurrentValue = HtmlDecode($this->icon->CurrentValue);
            }
            $this->icon->EditValue = HtmlEncode($this->icon->CurrentValue);
            $this->icon->PlaceHolder = RemoveHtml($this->icon->caption());

            // width
            $this->width->EditAttrs["class"] = "form-control";
            $this->width->EditCustomAttributes = "";
            $this->width->EditValue = HtmlEncode($this->width->CurrentValue);
            $this->width->PlaceHolder = RemoveHtml($this->width->caption());

            // height
            $this->height->EditAttrs["class"] = "form-control";
            $this->height->EditCustomAttributes = "";
            $this->height->EditValue = HtmlEncode($this->height->CurrentValue);
            $this->height->PlaceHolder = RemoveHtml($this->height->caption());

            // bar_width
            $this->bar_width->EditAttrs["class"] = "form-control";
            $this->bar_width->EditCustomAttributes = "";
            $this->bar_width->EditValue = HtmlEncode($this->bar_width->CurrentValue);
            $this->bar_width->PlaceHolder = RemoveHtml($this->bar_width->caption());

            // bar_name
            $this->bar_name->EditAttrs["class"] = "form-control";
            $this->bar_name->EditCustomAttributes = "";
            if (!$this->bar_name->Raw) {
                $this->bar_name->CurrentValue = HtmlDecode($this->bar_name->CurrentValue);
            }
            $this->bar_name->EditValue = HtmlEncode($this->bar_name->CurrentValue);
            $this->bar_name->PlaceHolder = RemoveHtml($this->bar_name->caption());

            // dont_reload
            $this->dont_reload->EditCustomAttributes = "";
            $this->dont_reload->EditValue = $this->dont_reload->options(false);
            $this->dont_reload->PlaceHolder = RemoveHtml($this->dont_reload->caption());

            // default_bookmark
            $this->default_bookmark->EditCustomAttributes = "";
            $this->default_bookmark->EditValue = $this->default_bookmark->options(false);
            $this->default_bookmark->PlaceHolder = RemoveHtml($this->default_bookmark->caption());

            // show_to_guests
            $this->show_to_guests->EditCustomAttributes = "";
            $this->show_to_guests->EditValue = $this->show_to_guests->options(false);
            $this->show_to_guests->PlaceHolder = RemoveHtml($this->show_to_guests->caption());

            // link
            $this->link->EditAttrs["class"] = "form-control";
            $this->link->EditCustomAttributes = "";
            if (!$this->link->Raw) {
                $this->link->CurrentValue = HtmlDecode($this->link->CurrentValue);
            }
            $this->link->EditValue = HtmlEncode($this->link->CurrentValue);
            $this->link->PlaceHolder = RemoveHtml($this->link->caption());

            // update_link
            $this->update_link->EditAttrs["class"] = "form-control";
            $this->update_link->EditCustomAttributes = "";
            if (!$this->update_link->Raw) {
                $this->update_link->CurrentValue = HtmlDecode($this->update_link->CurrentValue);
            }
            $this->update_link->EditValue = HtmlEncode($this->update_link->CurrentValue);
            $this->update_link->PlaceHolder = RemoveHtml($this->update_link->caption());

            // version
            $this->version->EditAttrs["class"] = "form-control";
            $this->version->EditCustomAttributes = "";
            if (!$this->version->Raw) {
                $this->version->CurrentValue = HtmlDecode($this->version->CurrentValue);
            }
            $this->version->EditValue = HtmlEncode($this->version->CurrentValue);
            $this->version->PlaceHolder = RemoveHtml($this->version->caption());

            // active
            $this->active->EditCustomAttributes = "";
            $this->active->EditValue = $this->active->options(false);
            $this->active->PlaceHolder = RemoveHtml($this->active->caption());

            // Add refer script

            // name
            $this->name->LinkCustomAttributes = "";
            $this->name->HrefValue = "";

            // folder
            $this->folder->LinkCustomAttributes = "";
            $this->folder->HrefValue = "";

            // icon
            $this->icon->LinkCustomAttributes = "";
            $this->icon->HrefValue = "";

            // width
            $this->width->LinkCustomAttributes = "";
            $this->width->HrefValue = "";

            // height
            $this->height->LinkCustomAttributes = "";
            $this->height->HrefValue = "";

            // bar_width
            $this->bar_width->LinkCustomAttributes = "";
            $this->bar_width->HrefValue = "";

            // bar_name
            $this->bar_name->LinkCustomAttributes = "";
            $this->bar_name->HrefValue = "";

            // dont_reload
            $this->dont_reload->LinkCustomAttributes = "";
            $this->dont_reload->HrefValue = "";

            // default_bookmark
            $this->default_bookmark->LinkCustomAttributes = "";
            $this->default_bookmark->HrefValue = "";

            // show_to_guests
            $this->show_to_guests->LinkCustomAttributes = "";
            $this->show_to_guests->HrefValue = "";

            // link
            $this->link->LinkCustomAttributes = "";
            $this->link->HrefValue = "";

            // update_link
            $this->update_link->LinkCustomAttributes = "";
            $this->update_link->HrefValue = "";

            // version
            $this->version->LinkCustomAttributes = "";
            $this->version->HrefValue = "";

            // active
            $this->active->LinkCustomAttributes = "";
            $this->active->HrefValue = "";
        }
        if ($this->RowType == ROWTYPE_ADD || $this->RowType == ROWTYPE_EDIT || $this->RowType == ROWTYPE_SEARCH) { // Add/Edit/Search row
            $this->setupFieldTitles();
        }

        // Call Row Rendered event
        if ($this->RowType != ROWTYPE_AGGREGATEINIT) {
            $this->rowRendered();
        }
    }

    // Validate form
    protected function validateForm()
    {
        global $Language;

        // Check if validation required
        if (!Config("SERVER_VALIDATE")) {
            return true;
        }
        if ($this->name->Required) {
            if (!$this->name->IsDetailKey && EmptyValue($this->name->FormValue)) {
                $this->name->addErrorMessage(str_replace("%s", $this->name->caption(), $this->name->RequiredErrorMessage));
            }
        }
        if ($this->folder->Required) {
            if (!$this->folder->IsDetailKey && EmptyValue($this->folder->FormValue)) {
                $this->folder->addErrorMessage(str_replace("%s", $this->folder->caption(), $this->folder->RequiredErrorMessage));
            }
        }
        if ($this->icon->Required) {
            if (!$this->icon->IsDetailKey && EmptyValue($this->icon->FormValue)) {
                $this->icon->addErrorMessage(str_replace("%s", $this->icon->caption(), $this->icon->RequiredErrorMessage));
            }
        }
        if ($this->width->Required) {
            if (!$this->width->IsDetailKey && EmptyValue($this->width->FormValue)) {
                $this->width->addErrorMessage(str_replace("%s", $this->width->caption(), $this->width->RequiredErrorMessage));
            }
        }
        if (!CheckInteger($this->width->FormValue)) {
            $this->width->addErrorMessage($this->width->getErrorMessage(false));
        }
        if ($this->height->Required) {
            if (!$this->height->IsDetailKey && EmptyValue($this->height->FormValue)) {
                $this->height->addErrorMessage(str_replace("%s", $this->height->caption(), $this->height->RequiredErrorMessage));
            }
        }
        if (!CheckInteger($this->height->FormValue)) {
            $this->height->addErrorMessage($this->height->getErrorMessage(false));
        }
        if ($this->bar_width->Required) {
            if (!$this->bar_width->IsDetailKey && EmptyValue($this->bar_width->FormValue)) {
                $this->bar_width->addErrorMessage(str_replace("%s", $this->bar_width->caption(), $this->bar_width->RequiredErrorMessage));
            }
        }
        if (!CheckInteger($this->bar_width->FormValue)) {
            $this->bar_width->addErrorMessage($this->bar_width->getErrorMessage(false));
        }
        if ($this->bar_name->Required) {
            if (!$this->bar_name->IsDetailKey && EmptyValue($this->bar_name->FormValue)) {
                $this->bar_name->addErrorMessage(str_replace("%s", $this->bar_name->caption(), $this->bar_name->RequiredErrorMessage));
            }
        }
        if ($this->dont_reload->Required) {
            if ($this->dont_reload->FormValue == "") {
                $this->dont_reload->addErrorMessage(str_replace("%s", $this->dont_reload->caption(), $this->dont_reload->RequiredErrorMessage));
            }
        }
        if ($this->default_bookmark->Required) {
            if ($this->default_bookmark->FormValue == "") {
                $this->default_bookmark->addErrorMessage(str_replace("%s", $this->default_bookmark->caption(), $this->default_bookmark->RequiredErrorMessage));
            }
        }
        if ($this->show_to_guests->Required) {
            if ($this->show_to_guests->FormValue == "") {
                $this->show_to_guests->addErrorMessage(str_replace("%s", $this->show_to_guests->caption(), $this->show_to_guests->RequiredErrorMessage));
            }
        }
        if ($this->link->Required) {
            if (!$this->link->IsDetailKey && EmptyValue($this->link->FormValue)) {
                $this->link->addErrorMessage(str_replace("%s", $this->link->caption(), $this->link->RequiredErrorMessage));
            }
        }
        if ($this->update_link->Required) {
            if (!$this->update_link->IsDetailKey && EmptyValue($this->update_link->FormValue)) {
                $this->update_link->addErrorMessage(str_replace("%s", $this->update_link->caption(), $this->update_link->RequiredErrorMessage));
            }
        }
        if ($this->version->Required) {
            if (!$this->version->IsDetailKey && EmptyValue($this->version->FormValue)) {
                $this->version->addErrorMessage(str_replace("%s", $this->version->caption(), $this->version->RequiredErrorMessage));
            }
        }
        if ($this->active->Required) {
            if ($this->active->FormValue == "") {
                $this->active->addErrorMessage(str_replace("%s", $this->active->caption(), $this->active->RequiredErrorMessage));
            }
        }

        // Return validate result
        $validateForm = !$this->hasInvalidFields();

        // Call Form_CustomValidate event
        $formCustomError = "";
        $validateForm = $validateForm && $this->formCustomValidate($formCustomError);
        if ($formCustomError != "") {
            $this->setFailureMessage($formCustomError);
        }
        return $validateForm;
    }

    // Add record
    protected function addRow($rsold = null)
    {
        global $Language, $Security;
        $conn = $this->getConnection();

        // Load db values from rsold
        $this->loadDbValues($rsold);
        if ($rsold) {
        }
        $rsnew = [];

        // name
        $this->name->setDbValueDef($rsnew, $this->name->CurrentValue, "", false);

        // folder
        $this->folder->setDbValueDef($rsnew, $this->folder->CurrentValue, "", false);

        // icon
        $this->icon->setDbValueDef($rsnew, $this->icon->CurrentValue, "", false);

        // width
        $this->width->setDbValueDef($rsnew, $this->width->CurrentValue, 0, false);

        // height
        $this->height->setDbValueDef($rsnew, $this->height->CurrentValue, 0, false);

        // bar_width
        $this->bar_width->setDbValueDef($rsnew, $this->bar_width->CurrentValue, null, false);

        // bar_name
        $this->bar_name->setDbValueDef($rsnew, $this->bar_name->CurrentValue, null, false);

        // dont_reload
        $tmpBool = $this->dont_reload->CurrentValue;
        if ($tmpBool != "1" && $tmpBool != "0") {
            $tmpBool = !empty($tmpBool) ? "1" : "0";
        }
        $this->dont_reload->setDbValueDef($rsnew, $tmpBool, null, strval($this->dont_reload->CurrentValue) == "");

        // default_bookmark
        $tmpBool = $this->default_bookmark->CurrentValue;
        if ($tmpBool != "1" && $tmpBool != "0") {
            $tmpBool = !empty($tmpBool) ? "1" : "0";
        }
        $this->default_bookmark->setDbValueDef($rsnew, $tmpBool, null, strval($this->default_bookmark->CurrentValue) == "");

        // show_to_guests
        $tmpBool = $this->show_to_guests->CurrentValue;
        if ($tmpBool != "1" && $tmpBool != "0") {
            $tmpBool = !empty($tmpBool) ? "1" : "0";
        }
        $this->show_to_guests->setDbValueDef($rsnew, $tmpBool, null, strval($this->show_to_guests->CurrentValue) == "");

        // link
        $this->link->setDbValueDef($rsnew, $this->link->CurrentValue, null, false);

        // update_link
        $this->update_link->setDbValueDef($rsnew, $this->update_link->CurrentValue, null, false);

        // version
        $this->version->setDbValueDef($rsnew, $this->version->CurrentValue, null, false);

        // active
        $tmpBool = $this->active->CurrentValue;
        if ($tmpBool != "1" && $tmpBool != "0") {
            $tmpBool = !empty($tmpBool) ? "1" : "0";
        }
        $this->active->setDbValueDef($rsnew, $tmpBool, 0, strval($this->active->CurrentValue) == "");

        // Call Row Inserting event
        $insertRow = $this->rowInserting($rsold, $rsnew);
        $addRow = false;
        if ($insertRow) {
            try {
                $addRow = $this->insert($rsnew);
            } catch (\Exception $e) {
                $this->setFailureMessage($e->getMessage());
            }
            if ($addRow) {
            }
        } else {
            if ($this->getSuccessMessage() != "" || $this->getFailureMessage() != "") {
                // Use the message, do nothing
            } elseif ($this->CancelMessage != "") {
                $this->setFailureMessage($this->CancelMessage);
                $this->CancelMessage = "";
            } else {
                $this->setFailureMessage($Language->phrase("InsertCancelled"));
            }
            $addRow = false;
        }
        if ($addRow) {
            // Call Row Inserted event
            $this->rowInserted($rsold, $rsnew);
        }

        // Clean upload path if any
        if ($addRow) {
        }

        // Write JSON for API request
        if (IsApi() && $addRow) {
            $row = $this->getRecordsFromRecordset([$rsnew], true);
            WriteJson(["success" => true, $this->TableVar => $row]);
        }
        return $addRow;
    }

    // Set up Breadcrumb
    protected function setupBreadcrumb()
    {
        global $Breadcrumb, $Language;
        $Breadcrumb = new Breadcrumb("index");
        $url = CurrentUrl();
        $Breadcrumb->add("list", $this->TableVar, $this->addMasterUrl("ArrowchatApplicationsList"), "", $this->TableVar, true);
        $pageId = ($this->isCopy()) ? "Copy" : "Add";
        $Breadcrumb->add("add", $pageId, $url);
    }

    // Setup lookup options
    public function setupLookupOptions($fld)
    {
        if ($fld->Lookup !== null && $fld->Lookup->Options === null) {
            // Get default connection and filter
            $conn = $this->getConnection();
            $lookupFilter = "";

            // No need to check any more
            $fld->Lookup->Options = [];

            // Set up lookup SQL and connection
            switch ($fld->FieldVar) {
                case "x_dont_reload":
                    break;
                case "x_default_bookmark":
                    break;
                case "x_show_to_guests":
                    break;
                case "x_active":
                    break;
                default:
                    $lookupFilter = "";
                    break;
            }

            // Always call to Lookup->getSql so that user can setup Lookup->Options in Lookup_Selecting server event
            $sql = $fld->Lookup->getSql(false, "", $lookupFilter, $this);

            // Set up lookup cache
            if ($fld->UseLookupCache && $sql != "" && count($fld->Lookup->Options) == 0) {
                $totalCnt = $this->getRecordCount($sql, $conn);
                if ($totalCnt > $fld->LookupCacheCount) { // Total count > cache count, do not cache
                    return;
                }
                $rows = $conn->executeQuery($sql)->fetchAll(\PDO::FETCH_BOTH);
                $ar = [];
                foreach ($rows as $row) {
                    $row = $fld->Lookup->renderViewRow($row);
                    $ar[strval($row[0])] = $row;
                }
                $fld->Lookup->Options = $ar;
            }
        }
    }

    // Page Load event
    public function pageLoad()
    {
        //Log("Page Load");
    }

    // Page Unload event
    public function pageUnload()
    {
        //Log("Page Unload");
    }

    // Page Redirecting event
    public function pageRedirecting(&$url)
    {
        // Example:
        //$url = "your URL";
    }

    // Message Showing event
    // $type = ''|'success'|'failure'|'warning'
    public function messageShowing(&$msg, $type)
    {
        if ($type == 'success') {
            //$msg = "your success message";
        } elseif ($type == 'failure') {
            //$msg = "your failure message";
        } elseif ($type == 'warning') {
            //$msg = "your warning message";
        } else {
            //$msg = "your message";
        }
    }

    // Page Render event
    public function pageRender()
    {
        //Log("Page Render");
    }

    // Page Data Rendering event
    public function pageDataRendering(&$header)
    {
        // Example:
        //$header = "your header";
    }

    // Page Data Rendered event
    public function pageDataRendered(&$footer)
    {
        // Example:
        //$footer = "your footer";
    }

    // Form Custom Validate event
    public function formCustomValidate(&$customError)
    {
        // Return error message in CustomError
        return true;
    }
}
