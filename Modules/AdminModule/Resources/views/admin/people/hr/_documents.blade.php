<div class="people-ws-head"><div><h1>Documents</h1><p>Mark a file on record after you have opened it, or send it back with a reason.</p></div></div>
<article class="people-ws-card people-ws-scroll">
    <table>
        <thead><tr><th>Person</th><th>Document</th><th>Received</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @foreach($documents as $document)
            <tr>
                <td>{{ $document->user ? $workspace->displayName($document->user) : '—' }}</td>
                <td>{{ $document->title }}@if($document->rejection_note)<div class="people-ws-note">{{ $document->rejection_note }}</div>@endif</td>
                <td>{{ $document->uploaded_at ? $document->uploaded_at->format('j M Y') : '—' }}</td>
                <td>@include('adminmodule::admin.people._badge', ['status' => $document->status])</td>
                <td class="people-ws-actions">
                    @if($document->file_path)
                        <a class="btn-pw" href="{{ route('admin.people.documents.download', $document) }}">Download</a>
                    @endif
                    @if($document->status === 'pending')
                        <form method="post" action="{{ route('admin.people.records.documents.verify', $document) }}">
                            @csrf
                            <button class="btn-pw" type="submit">Mark on file</button>
                        </form>
                        <form method="post" action="{{ route('admin.hr.documents.reject', $document) }}">
                            @csrf
                            <input name="rejection_note" placeholder="Reason" required maxlength="500">
                            <button class="btn-pw danger" type="submit">Send back</button>
                        </form>
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</article>
<form class="people-ws-card people-ws-mt" method="post" action="{{ route('admin.hr.documents.ask') }}">
    @csrf
    <h2>Ask for a document</h2>
    <div class="people-ws-form-grid">
        <div class="field"><label for="doc_user">Person</label>
            <select id="doc_user" name="user_id" required>
                @foreach($staff as $person)
                    <option value="{{ $person->id }}">{{ $workspace->displayName($person) }}</option>
                @endforeach
            </select>
        </div>
        <div class="field"><label for="doc_title">Name</label><input id="doc_title" name="title" required maxlength="80" placeholder="Offer letter"></div>
    </div>
    <button class="btn-pw primary" type="submit">Add to the file</button>
</form>
