<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>{{ $title }}</title>
  <style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111827; }
    h1 { font-size: 20px; margin-bottom: 16px; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #d1d5db; padding: 8px; text-align: left; vertical-align: top; }
    th { background: #f3f4f6; }
  </style>
</head>
<body>
  <h1>{{ $title }}</h1>
  <table>
    <thead>
      <tr>
        @foreach($headers as $header)
        <th>{{ $header }}</th>
        @endforeach
      </tr>
    </thead>
    <tbody>
      @foreach($rows as $row)
      <tr>
        @foreach($row as $cell)
        <td>{{ $cell }}</td>
        @endforeach
      </tr>
      @endforeach
    </tbody>
  </table>
</body>
</html>
