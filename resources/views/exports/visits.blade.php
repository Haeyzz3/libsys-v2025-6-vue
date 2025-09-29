<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Library Visits Export</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; }
        h1 { font-size: 18px; margin-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #444; padding: 4px 6px; text-align: left; }
        th { background: #f5f5f5; }
        .meta { font-size: 11px; color: #555; }
    </style>
</head>
<body>
<h1>Library Visits</h1>
<p class="meta">
    Filter: {{ ucfirst($filter) }} | Period: {{ $start->format('Y-m-d H:i') }} to {{ $end->format('Y-m-d H:i') }}
    @if($program !== 'all') | Program: {{ $program }} @endif
</p>
<table>
    <thead>
    <tr>
        <th>ID</th>
        <th>First Name</th>
        <th>Last Name</th>
        <th>Program</th>
        <th>Entry Time</th>
        <th>Exit Time</th>
    </tr>
    </thead>
    <tbody>
    @forelse($rows as $row)
        <tr>
            <td>{{ $row->id }}</td>
            <td>{{ $row->first_name }}</td>
            <td>{{ $row->last_name }}</td>
            <td>{{ $row->program_code ?? 'UNASSIGNED' }}</td>
            <td>{{ $row->entry_time }}</td>
            <td>{{ $row->exit_time ?? '' }}</td>
        </tr>
    @empty
        <tr>
            <td colspan="6" style="text-align:center;">No records found for this period.</td>
        </tr>
    @endforelse
    </tbody>
</table>
</body>
</html>

