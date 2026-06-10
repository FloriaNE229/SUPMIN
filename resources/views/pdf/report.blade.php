<!DOCTYPE html>
<html lang="en"></html>
<head>     
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report PDF</title>
</head>
<body>
    <h1>Rapport Mission</h1>

<p><strong>Titre :</strong> {{ $report['mission']['title'] }}</p>
<p><strong>Statut :</strong> {{ $report['mission']['status'] }}</p>

<h2>Réponses</h2>

@foreach($report['responses'] as $form)
    <h3>{{ $form['form_title'] }}</h3>

    @foreach($form['sections'] as $section)
        <h4>{{ $section['section_title'] }}</h4>

        @foreach($section['questions'] as $q)
            <p>{{ $q['question'] }} : {{ $q['answer'] }}</p>
        @endforeach
    @endforeach
@endforeach
</body>
</html>