<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

use App\Models\Vocabulary;

class GameController extends Controller
{
    public function adivinarPalabra()
    {
        // Seleccionar una palabra aleatoria
        $vocabulario = Vocabulary::inRandomOrder()->first();

        // Mezclar las letras del título
        $desordenada = str_shuffle($vocabulario->title);

        return view('games.adivinar', compact('vocabulario', 'desordenada'));
    }


    public function verificarRespuesta(Request $request)
    {
        $respuesta = $request->input('respuesta');
        $palabra_correcta = $request->input('palabra_correcta');
        $puntuacion = session('puntuacion', 0); // Obtener la puntuación actual de la sesión.
    
        if (strtolower($respuesta) === strtolower($palabra_correcta)) {
            // Si la respuesta es correcta, aumenta la puntuación.
            $puntuacion += 1;
            session(['puntuacion' => $puntuacion]);
            return back()->with('success', '¡Correcto! La palabra era ' . $palabra_correcta);
        }
    
        return back()->with('error', 'Incorrecto. Inténtalo de nuevo.');
    }
    
    

    public function mostrarResultados()
    {
        $puntuacion = session('puntuacion', 0);
        session()->forget('puntuacion'); // Limpiar la puntuación de la sesión.

        return view('games.resultados', compact('puntuacion'));
    }

    public function reiniciarJuego()
    {
        // Selecciona una palabra aleatoria y reinicia el temporizador
        $vocabulario = Vocabulary::inRandomOrder()->first();

        return view('games.adivinar', [
            'vocabulario' => $vocabulario,
            'desordenada' => str_shuffle($vocabulario->title),
            'tiempo' => 60 // Tiempo inicial en segundos
        ]);
    }

    // ahorcado



    private $intentosMaximos = 6;

    public function iniciarJuego()
    {
        // Selecciona una palabra aleatoria de la base de datos
        $vocabulario = Vocabulary::inRandomOrder()->first();
        $palabraCorrecta = $vocabulario ? $vocabulario->title : 'programacion'; // Palabra por defecto si no hay registros
        $pista = $vocabulario ? $vocabulario->description : 'Una pista por defecto'; // Pista por defecto
    
        session([
            'palabraCorrecta' => $palabraCorrecta,
            'pista' => $pista,
            'intentos' => $this->intentosMaximos,
            'estadoJuego' => 'en juego',
            'letrasErradas' => [],
            'letrasCorrectas' => [], // También inicializa las letras correctas
        ]);
    }

    public function mostrarJuego()
    {
        $palabraCorrecta = session('palabraCorrecta');
        $pista = session('pista');
        $intentos = session('intentos');
        $estadoJuego = session('estadoJuego');
        $letrasErradas = session('letrasErradas', []);
        $letrasCorrectas = session('letrasCorrectas', []);
    
        // Mostrar la palabra con las letras adivinadas
        $palabraOculta = array_map(function($letra) use ($letrasCorrectas) {
            return in_array($letra, $letrasCorrectas) ? $letra : '_';
        }, str_split($palabraCorrecta));
    
        return view('games.hangman', compact('palabraOculta', 'intentos', 'estadoJuego', 'palabraCorrecta', 'pista', 'letrasErradas'));
    }
    

    public function adivinarPalabraCompleta(Request $request)
    {
        $respuesta = strtolower(trim($request->input('respuesta'))); // Limpia y convierte a minúsculas
        $palabraCorrecta = strtolower(session('palabraCorrecta'));
        $intentos = session('intentos');
        $letrasErradas = session('letrasErradas');
    
        if (!$respuesta) {
            return redirect()->route('ahorcado.mostrar')->with('mensaje', 'Debes ingresar una palabra.');
        }
    
        if ($respuesta === $palabraCorrecta) {
            session(['estadoJuego' => 'ganado']);
        } else {
            $intentos--;
            $letrasErradas[] = $respuesta; // Agrega la respuesta incorrecta a las letras erradas
            session(['intentos' => $intentos]);
            session(['letrasErradas' => $letrasErradas]);
    
            if ($intentos <= 0) {
                session(['estadoJuego' => 'perdido']);
            }
        }
    
        return redirect()->route('ahorcado.mostrar');
    }

    public function reiniciarJuegoAhorcado()
{
    // Recupera la palabra y la pista actuales de la sesión
    $palabraCorrecta = session('palabraCorrecta');
    $pista = session('pista');

    // Reinicia los valores de la sesión para comenzar el juego desde cero
    session([
        'intentos' => $this->intentosMaximos,
        'estadoJuego' => 'en juego',
        'letrasErradas' => [],
        'letrasCorrectas' => [], // Reinicia las letras correctas
    ]);

    return redirect()->route('ahorcado.mostrar')->with('mensaje', 'El juego ha sido reiniciado.');
}

    
}