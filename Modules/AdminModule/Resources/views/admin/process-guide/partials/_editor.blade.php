<aside class="pg-editor-panel" data-pg-editor-panel hidden aria-label="Flowchart editor">
    <div class="pg-editor-header">
        <div>
            <h4>Edit flowchart</h4>
            <p class="pg-editor-sub">Drag shapes · double-click text · assign groups · save when done</p>
        </div>
        <button type="button" class="pg-editor-close" data-pg-edit-close aria-label="Close editor">&times;</button>
    </div>

    <div class="pg-editor-body">
        <section class="pg-editor-section">
            <h5>Selected shape</h5>
            <p class="pg-editor-empty" data-pg-edit-no-selection>Click a shape on the board</p>
            <div class="pg-editor-fields" data-pg-edit-selection hidden>
                <label class="pg-editor-label">Label text
                    <textarea rows="3" data-pg-edit-node-text placeholder="Shape label"></textarea>
                </label>
                <label class="pg-editor-label">Shape type
                    <select data-pg-edit-node-shape>
                        <option value="rectangle">Action / channel box</option>
                        <option value="round_rectangle">Rounded box</option>
                        <option value="rhombus">Decision</option>
                        <option value="wedge_round_rectangle_callout">Message</option>
                    </select>
                </label>
                <button type="button" class="pg-editor-danger" data-pg-edit-delete-node>Delete shape</button>
            </div>
        </section>

        <section class="pg-editor-section">
            <h5>Add shape</h5>
            <div class="pg-editor-btn-row">
                <button type="button" data-pg-add-shape="rectangle">+ Action</button>
                <button type="button" data-pg-add-shape="rhombus">+ Decision</button>
                <button type="button" data-pg-add-shape="wedge_round_rectangle_callout">+ Message</button>
            </div>
        </section>

        <section class="pg-editor-section">
            <h5>Step groups</h5>
            <div class="pg-editor-groups" data-pg-groups-list></div>
            <div class="pg-editor-btn-row">
                <button type="button" data-pg-add-group>+ New group</button>
                <button type="button" data-pg-assign-group title="Add selected shape(s) to active group">Add to group</button>
            </div>
            <div class="pg-editor-fields" data-pg-group-form hidden>
                <label class="pg-editor-label">Step number
                    <input type="number" min="1" data-pg-group-step>
                </label>
                <label class="pg-editor-label">Group title
                    <input type="text" data-pg-group-title>
                </label>
                <label class="pg-editor-label">Subtitle
                    <input type="text" data-pg-group-subtitle>
                </label>
                <label class="pg-editor-label">Intro (detail panel)
                    <textarea rows="2" data-pg-group-intro></textarea>
                </label>
                <label class="pg-editor-label">Notes (one per line)
                    <textarea rows="3" data-pg-group-notes></textarea>
                </label>
                <label class="pg-editor-label">Detail sections (JSON)
                    <textarea rows="4" data-pg-group-sections placeholder='[{"title":"Section","items":["Item 1"]}]'></textarea>
                </label>
                <button type="button" class="pg-editor-danger" data-pg-delete-group>Delete group</button>
            </div>
        </section>
    </div>

    <div class="pg-editor-footer">
        <button type="button" class="pg-editor-save" data-pg-save>Save changes</button>
        <span class="pg-editor-status" data-pg-save-status aria-live="polite"></span>
    </div>
</aside>
