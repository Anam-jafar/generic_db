@extends('layouts.app')

@section('content')
    <h1>Activity Logs</h1>

    <table class="table">
        <thead>
            <tr>
                <th>User</th>
                <th>Action</th>
                <th>Collection Name</th>
                <th>Timestamp</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($logs as $log)
                <tr class="log-row" data-log="{{ json_encode($log->document_before) }}">
                    <td>
                        <span class="arrow" style="cursor: pointer;">▼</span> <!-- Arrow indicator -->
                        {{ $log->user }}
                    </td>
                    <td>{{ $log->action }}</td>
                    <td>{{ $log->collection_name }}</td>
                    <td>{{ $log->timestamp }}</td>
                </tr>
                <tr class="collapse-row" style="display: none;">
                    <td colspan="4">
                        <div class="card">
                            <div class="card-body">
                                <pre id="documentBefore-{{ $loop->index }}" class="document-before"></pre>
                            </div>
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <script>
        document.querySelectorAll('.log-row').forEach(row => {
            row.addEventListener('click', function() {
                const collapseRow = this.nextElementSibling; // Get the corresponding collapse row
                const documentBefore = this.dataset.log; // Retrieve the document before data
                const arrow = this.querySelector('.arrow'); // Get the arrow element

                if (collapseRow.style.display === "none") {
                    collapseRow.style.display = "table-row"; // Show the collapsible row
                    collapseRow.querySelector('.document-before').textContent = documentBefore; // Populate the card
                    arrow.textContent = "▲"; // Change arrow to up
                } else {
                    collapseRow.style.display = "none"; // Hide the collapsible row
                    arrow.textContent = "▼"; // Change arrow to down
                }
            });
        });
    </script>

    <style>
        .card {
            border: 1px solid #ccc;
            border-radius: 4px;
            margin-top: 10px;
        }
        .card-body {
            padding: 15px;
            background-color: #f9f9f9;
        }
        .arrow {
            margin-right: 5px; /* Add some space between arrow and text */
        }
    </style>
@endsection
