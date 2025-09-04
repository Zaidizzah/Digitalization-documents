@php
    // Declaring variable to provide actual route from url route
    $FORM_ROUTE = NULL;
    if (route_check("userguides.create")) {
        $FORM_ROUTE = route("userguides.store");
    } else if (route_check("userguides.create.named")) {
        $FORM_ROUTE = route("userguides.store.named", ($document_type instanceof App\Models\DocumentType ? $document_type->name : NULL));
    } else if (route_check("userguides.edit")) {
        $CURRENT_DATA = $CURRENT_DATA ?? NULL;
        $FORM_ROUTE = route("userguides.update", ($CURRENT_DATA instanceof App\Models\UserGuides ? $CURRENT_DATA->id : NULL));
    } else {
        $CURRENT_DATA = $CURRENT_DATA ?? NULL;
        $FORM_ROUTE = route("userguides.update.named", ['name' => ($document_type instanceof App\Models\DocumentType ? $document_type->name : NULL), 'id' => ($CURRENT_DATA instanceof App\Models\UserGuides ? $CURRENT_DATA->id : NULL)]);
    }
@endphp 

@extends("layouts.main")

@section("content")

    @includeWhen($document_type instanceof App\Models\DocumentType, 'partials.document-type-action-menu', ['document_type' => $document_type])
    @includeWhen(($document_type instanceof App\Models\DocumentType) === FALSE, 'partials.setting-menu', ['document_type' => NULL])

    <form action="{{ $FORM_ROUTE }}" method="POST" id="form-settings" data-action="{{ (route_check("userguides.edit", "userguides.edit.named") ? "update" : "create") }}">
        @csrf

        @if (route_check("userguides.edit", "userguides.edit.named"))
            @method('PUT')
        @endif

        <!-- Accordion helper for writing in editor -->
        <div class="accordion mb-3" id="--accordion-editor-helper-guide" aria-label="Accordion helper for writing in editor">
            <div class="accordion-item" style="border-color: var(--primary-color)">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#--accordion-editor-helper-guide-content" aria-expanded="true" aria-controls="--accordion-editor-helper-guide-content">
                        Editor helper
                    </button>
                </h2>
                <div class="accordion-collapse collapse" data-bs-parent="#--accordion-editor-helper-guide" id="--accordion-editor-helper-guide-content">
                    <div class="accordion-body">
                        <h1>Panduan Dasar Syntax Markdown</h1>
        
                        <p>Markdown adalah bahasa markup ringan yang dapat Anda gunakan untuk menambahkan elemen pemformatan ke dokumen teks biasa.</p>

                        <h2>Headings</h2>
                        <p>Untuk membuat heading, tambahkan tanda pagar (#) di depan kata atau frasa. Jumlah tanda pagar yang Anda gunakan harus sesuai dengan level heading.</p>
                        
                        <div class="example">
                            <div class="markdown-input"># Heading level 1
                    ## Heading level 2
                    ### Heading level 3
                    #### Heading level 4
                    ##### Heading level 5
                    ###### Heading level 6</div>
                            
                            <div class="markdown-output">
                                <h1>Heading level 1</h1>
                                <h2>Heading level 2</h2>
                                <h3>Heading level 3</h3>
                                <h4>Heading level 4</h4>
                                <h5>Heading level 5</h5>
                                <h6>Heading level 6</h6>
                            </div>
                        </div>

                        <h2>Emphasis</h2>
                        <p>Anda dapat menambahkan emphasis dengan membuat teks menjadi bold atau italic.</p>

                        <div class="example">
                            <div class="markdown-input">**Teks ini bold**
                    *Teks ini italic*
                    ***Teks ini bold dan italic***</div>
                            
                            <div class="markdown-output">
                                <strong>Teks ini bold</strong><br>
                                <em>Teks ini italic</em><br>
                                <strong><em>Teks ini bold dan italic</em></strong>
                            </div>
                        </div>

                        <h2>Blockquotes</h2>
                        <p>Untuk membuat blockquote, tambahkan > di depan paragraf.</p>

                        <div class="example">
                            <div class="markdown-input">> Dorothy mengikuti jalan batu kuning yang menuju ke kota Emerald.</div>
                            
                            <div class="markdown-output">
                                <blockquote>
                                    <p>Dorothy mengikuti jalan batu kuning yang menuju ke kota Emerald.</p>
                                </blockquote>
                            </div>
                        </div>

                        <h2>Lists</h2>
                        <p>Anda dapat mengatur item dalam daftar terurut dan tidak terurut.</p>

                        <h3>Unordered Lists</h3>
                        <div class="example">
                            <div class="markdown-input list">- Item pertama
                    - Item kedua
                    - Item ketiga
                        - Sub item</div>
                            
                            <div class="markdown-output">
                                <ul>
                                    <li>Item pertama</li>
                                    <li>Item kedua</li>
                                    <li>Item ketiga
                                        <ul>
                                            <li>Sub item</li>
                                        </ul>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <h3>Ordered Lists</h3>
                        <div class="example">
                            <div class="markdown-input list">1. Item pertama
                    2. Item kedua
                    3. Item ketiga
                        1. Sub item</div>
                            
                            <div class="markdown-output">
                                <ol>
                                    <li>Item pertama</li>
                                    <li>Item kedua</li>
                                    <li>Item ketiga
                                        <ol>
                                            <li>Sub item</li>
                                        </ol>
                                    </li>
                                </ol>
                            </div>
                        </div>

                        <h2>Code</h2>
                        <p>Untuk menunjukkan bahwa kata atau frasa adalah kode, bungkus dengan backticks (`).</p>

                        <div class="example">
                            <div class="markdown-input">Dalam terminal, ketik `nano`.</div>
                            
                            <div class="markdown-output">
                                <p>Dalam terminal, ketik <code>nano</code>.</p>
                            </div>
                        </div>

                        <h3>Code Blocks</h3>
                        <div class="example">
                            <div class="markdown-input">```
                    {
                    "firstName": "John",
                    "lastName": "Smith",
                    "age": 25
                    }
                    ```</div>
                            
                            <div class="markdown-output">
                                <pre><code>{
                    "firstName": "John",
                    "lastName": "Smith",
                    "age": 25
                    }</code></pre>
                            </div>
                        </div>

                        <h2>Horizontal Rules</h2>
                        <p>Untuk membuat horizontal rule, gunakan tiga atau lebih asterisk (***), dash (---), atau underscore (___) pada baris terpisah.</p>

                        <div class="example">
                            <div class="markdown-input">---</div>
                            
                            <div class="markdown-output">
                                <hr>
                            </div>
                        </div>

                        <h2>Links</h2>
                        <p>Untuk membuat link, bungkus teks link dalam kurung siku (seperti [Duck Duck Go]) dan kemudian ikuti segera dengan URL dalam tanda kurung (seperti (https://duckduckgo.com)).</p>

                        <div class="example">
                            <div class="markdown-input">Website favorit saya adalah [Duck Duck Go](https://duckduckgo.com).</div>
                            
                            <div class="markdown-output">
                                <p>Website favorit saya adalah <a href="https://duckduckgo.com">Duck Duck Go</a>.</p>
                            </div>
                        </div>

                        <h2>Images</h2>
                        <p>Untuk menambahkan gambar, tambahkan tanda seru (!), diikuti dengan alt text dalam kurung siku, dan path atau URL ke file gambar dalam tanda kurung.</p>

                        <div class="example">
                            <div class="markdown-input">![Philadelphia's Magic Gardens.](https://placehold.co/400x400)</div>
                            
                            <div class="markdown-output">
                                <img src="https://placehold.co/400x400" alt="Philadelphia's Magic Gardens.">
                            </div>
                        </div>

                        <h2>Tables</h2>
                        <p>Untuk menambahkan tabel, gunakan tiga atau lebih hyphens (---) untuk membuat header setiap kolom, dan gunakan pipes (|) untuk memisahkan setiap kolom.</p>

                        <div class="example">
                            <div class="markdown-input">| Syntax      | Description |
                    | ----------- | ----------- |
                    | Header      | Title       |
                    | Paragraph   | Text        |</div>
                            
                            <div class="markdown-output">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Syntax</th>
                                            <th>Description</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Header</td>
                                            <td>Title</td>
                                        </tr>
                                        <tr>
                                            <td>Paragraph</td>
                                            <td>Text</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <h2>Strikethrough</h2>
                        <p>Untuk membuat teks yang dicoret, gunakan dua tilde (~) sebelum dan sesudah teks.</p>

                        <div class="example">
                            <div class="markdown-input">~~Teks ini dicoret~~</div>
                            
                            <div class="markdown-output">
                                <del>Teks ini dicoret</del>
                            </div>
                        </div>

                        <h2>Task Lists</h2>
                        <p>Untuk membuat checklist atau task list, gunakan tanda kurung siku dengan spasi atau x di dalamnya.</p>

                        <div class="example">
                            <div class="markdown-input">- [x] Tugas yang sudah selesai
                    - [ ] Tugas yang belum selesai
                    - [x] Tugas lain yang sudah selesai
                    - [ ] Tugas yang masih harus dikerjakan</div>
                            
                            <div class="markdown-output">
                                <ul style="list-style: none; padding-left: 0;">
                                    <li><input type="checkbox" checked disabled style="margin-right: 8px;"> Tugas yang sudah selesai</li>
                                    <li><input type="checkbox" disabled style="margin-right: 8px;"> Tugas yang belum selesai</li>
                                    <li><input type="checkbox" checked disabled style="margin-right: 8px;"> Tugas lain yang sudah selesai</li>
                                    <li><input type="checkbox" disabled style="margin-right: 8px;"> Tugas yang masih harus dikerjakan</li>
                                </ul>
                            </div>
                        </div>

                        <h2>Alerts</h2>
                        <p>Untuk membuat alert box dengan berbagai jenis peringatan, gunakan syntax khusus dengan blockquote.</p>

                        <div class="example">
                            <div class="markdown-input">> [!NOTE]
                    > Ini adalah catatan penting yang perlu diperhatikan.

                    > [!TIP]
                    > Tips berguna untuk meningkatkan produktivitas.

                    > [!IMPORTANT]
                    > Informasi yang sangat penting dan harus dibaca.

                    > [!WARNING]
                    > Peringatan tentang sesuatu yang berbahaya.

                    > [!CAUTION]
                    > Hati-hati dengan langkah ini, bisa berakibat fatal.</div>
                            
                            <div class="markdown-output">
                                <div class="alert alert-note" role="alert">
                                    <div class="alert-heading">
                                        <img class="alert-heading-icon" src="{{ asset('resources/images/icons/alert-note.svg') }}" width="24" height="24" alt="note">
                                        <h4>NOTE</h4>
                                    </div>
                                    <div class="alert-content">
                                        Ini adalah catatan penting yang perlu diperhatikan.
                                    </div>
                                </div>
                                <div class="alert alert-tip" role="alert">
                                    <div class="alert-heading">
                                        <img class="alert-heading-icon" src="{{ asset('resources/images/icons/alert-tip.svg') }}" width="24" height="24" alt="tip">
                                        <h4>TIP</h4>
                                    </div>
                                    <div class="alert-content">
                                        Tips berguna untuk meningkatkan produktivitas.
                                    </div>
                                </div>
                                <div class="alert alert-important" role="alert">
                                    <div class="alert-heading">
                                        <img class="alert-heading-icon" src="{{ asset('resources/images/icons/alert-important.svg') }}" width="24" height="24" alt="important">
                                        <h4>IMPORTANT</h4>
                                    </div>
                                    <div class="alert-content">
                                        Informasi yang sangat penting dan harus dibaca.
                                    </div>
                                </div>
                                <div class="alert alert-warning" role="alert">
                                    <div class="alert-heading">
                                        <img class="alert-heading-icon" src="{{ asset('resources/images/icons/alert-warning.svg') }}" width="24" height="24" alt="warning">
                                        <h4>WARNING</h4>
                                    </div>
                                    <div class="alert-content">
                                        Peringatan tentang sesuatu yang berbahaya.
                                    </div>
                                </div>
                                <div class="alert alert-caution" role="alert">
                                    <div class="alert-heading">
                                        <img class="alert-heading-icon" src="{{ asset('resources/images/icons/alert-caution.svg') }}" width="24" height="24" alt="caution">
                                        <h4>CAUTION</h4>
                                    </div>
                                    <div class="alert-content">
                                        Hati-hati dengan langkah ini, bisa berakibat fatal.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <h2>Summary/Details</h2>
                        <p>Untuk membuat konten yang bisa dibuka/tutup (collapsible), gunakan syntax summary khusus.</p>

                        <div class="example">
                            <div class="markdown-input">:::summary Klik untuk melihat detail
                    Ini adalah konten yang tersembunyi.

                    **Konten ini** bisa berisi *markdown* lainnya.

                    - Item 1
                    - Item 2
                    :::</div>
                            
                            <div class="markdown-output">
                                <details style="border: 1px solid #d0d7de; border-radius: 6px; padding: 16px; margin: 16px 0;">
                                    <summary style="cursor: pointer; font-weight: 600; margin-bottom: 8px;">Klik untuk melihat detail</summary>
                                    <div style="margin-top: 8px;">
                                        <p>Ini adalah konten yang tersembunyi.</p>
                                        <p><strong>Konten ini</strong> bisa berisi <em>markdown</em> lainnya.</p>
                                        <ul>
                                            <li>Item 1</li>
                                            <li>Item 2</li>
                                        </ul>
                                    </div>
                                </details>
                            </div>
                        </div>

                        <h2>Advanced Lists</h2>
                        <p>Markdown mendukung berbagai jenis list dengan numbering yang berbeda:</p>

                        <h3>Roman Numerals</h3>
                        <div class="example">
                            <div class="markdown-input list">I. Item pertama
                    II. Item kedua
                    III. Item ketiga
                        i. Sub item lowercase
                        ii. Sub item lainnya</div>
                            
                            <div class="markdown-output">
                                <ol style="list-style-type: upper-roman;">
                                    <li>Item pertama</li>
                                    <li>Item kedua</li>
                                    <li>Item ketiga
                                        <ol style="list-style-type: lower-roman;">
                                            <li>Sub item lowercase</li>
                                            <li>Sub item lainnya</li>
                                        </ol>
                                    </li>
                                </ol>
                            </div>
                        </div>

                        <h3>Alphabetical Lists</h3>
                        <div class="example">
                            <div class="markdown-input list">A. Item dengan huruf besar
                    B. Item kedua
                    C. Item ketiga
                        a. Sub item huruf kecil
                        b. Sub item lainnya</div>
                            
                            <div class="markdown-output">
                                <ol style="list-style-type: upper-alpha;">
                                    <li>Item dengan huruf besar</li>
                                    <li>Item kedua</li>
                                    <li>Item ketiga
                                        <ol style="list-style-type: lower-alpha;">
                                            <li>Sub item huruf kecil</li>
                                            <li>Sub item lainnya</li>
                                        </ol>
                                    </li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Editor Content HTML for user guide -->
        <div class="editor-content-wrapper" id="editor-content-wrapper" role="region"></div>

        @isset($user_guides)
            <!-- User Guides Tree for picking -->
            <div class="user-guides-wrapper" id="user-guides-wrapper" aria-labelledby="user-guides-header" role="region">
                <div class="user-guides-header" role="heading">
                    <h3 class="user-guides-header-title" id="user-guides-header">User Guides Selector</h3>
                    <p class="user-guides-header-subtitle">Pick an available user guides data for new <mark>{{ $document_type instanceof App\Models\DocumentType ? "Document type '{$document_type->name}'" : 'General' }}</mark> user guides content or not choosing any will create for new <mark>{{ $document_type instanceof App\Models\DocumentType ? "Document type '{$document_type->name}'" : 'General' }}</mark> user guides content</p>
                </div>

                <div class="user-guides-tree-wrapper" id="user-guides-tree-wrapper">
                    <div class="user-guides-tree-view" id="user-guides-tree-view" role="tree">
                        @if ($user_guides->isNotEmpty()) 
                            @include('partials.userguide-create-edit-tree-item', ['USER_GUIDES' => $user_guides, 'CURRENT_DATA' => $CURRENT_DATA ?? NULL])
                        @else
                            <p style="text-align: center; color: #586069; padding: 20px;">No data found</p>
                        @endif
                    </div>
                </div>

                @if ($user_guides->isNotEmpty())
                    <div class="user-guides-actions-wrapper">
                        {{-- Check if data has more than 10 data's --}}
                        @if ($user_guides->lastPage() > 1)
                            <button type="button" role="button" class="btn btn-info btn-load-more-data" id="user-guides-load-more-data-btn" title="Button: to load more user guide data's" data-current-page="{{ $user_guides->currentPage() }}" data-last-page="{{ $user_guides->lastPage() }}" aria-controls="user-guides-tree-view">
                                Load More
                            </button>
                        @endif
                        <button type="button" role="button" class="btn btn-secondary btn-clear-selection" id="user-guides-clear-selection-btn" title="Button: to clear input has selected before">
                            Clear Selection
                        </button>
                    </div>
                @endif
            </div>
        @endisset
        
        <div class="tile mt-3" aria-label="Tile section of user guides">
            <div class="tile-body">
                <div class="form-group row g-1 mb-3">
                    <label for="title" class="form-label col-sm-3">Title<span aria-label="required" class="text-danger">*</span></label>
                    <div class="col-sm-9">
                        <input type="text" name="title" class="form-control" id="title"
                        minlength="1"
                        maxlength="150"
                        pattern="^[a-zA-Z][a-zA-Z0-9_ ]{0,150}$"
                        title="Only letters and numbers are allowed, and maximum of 150 characters."
                        aria-label="Title" aria-required="true" value="{{ (isset($CURRENT_DATA) && $CURRENT_DATA->title) ? $CURRENT_DATA->title : old('title') }}" required>
                    </div>
                </div>
            </div>
            <div class="tile-footer">
                <button type="submit" role="button" class="btn btn-primary btn-sm" title="Button: to save changes for user guides config"><i class="bi bi-save fs-5"></i>Save</button>
            </div>
        </div>
    </form>

@endsection