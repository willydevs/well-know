<?php
/*
Plugin Name: Well Know - Security Scanner
Description: Um plugin de segurança simples para escanear e remover códigos maliciosos.
Version: 1.0
Author: Willy Elvis
*/

if (!defined('ABSPATH')) {
    exit; // Evita acesso direto
}

class BemSeiSecurityScanner {
    private $malicious_codes = [
        'codigo>Malicioso arquivo 1',
        'codigo>Malicioso arquivo 2',
        'codigo>Malicioso arquivo 3',
    ];

    private $directories_to_scan = [
        ABSPATH,                 
        ABSPATH . 'wp-admin/',
        ABSPATH . 'wp-includes/',
        ABSPATH . 'wp-content/'
    ];

    public function __construct() {
        add_action('admin_menu', [$this, 'register_admin_menu']);
    }

    public function register_admin_menu() {
        add_menu_page(
            'Well Know - Security Scanner',
            'Well Know - Security ',
            'manage_options',
            'bem-sei-security',
            [$this, 'scanner_page']
        );
    }

    public function scanner_page() {
        echo '<div class="wrap">';
        echo '<h1>Well Know - Security Scanner</h1>';
        echo '<form method="post" action="">';
        echo '<input type="submit" name="scan" class="button button-primary" value="Escanear agora">';
        echo '</form>';

        if (isset($_POST['scan'])) {
            $this->scan_files();
        }

        echo '</div>';
    }

    private function scan_files() {
        echo '<h2>Resultado do Escaneamento:</h2>';
        foreach ($this->directories_to_scan as $directory) {
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
            foreach ($files as $file) {
                if ($file->isFile() && pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                    $this->scan_file($file->getPathname());
                }
            }
        }
    }

    private function scan_file($file_path) {
        $file_contents = file($file_path);
        $found = false;

        foreach ($file_contents as $line_num => $line) {
            foreach ($this->malicious_codes as $code) {
                if (strpos($line, $code) !== false) {
                    $found = true;
                    // Log de depuração
                    error_log("Código malicioso encontrado no arquivo: $file_path na linha: $line_num");

                    // Exibe o resultado no painel do plugin
                    echo "<p>Possível código malicioso encontrado em <strong>{$file_path}</strong> na linha <strong>" . ($line_num + 1) . "</strong>: <code>" . htmlspecialchars($line) . "</code></p>";
                    echo '<form method="post" action="">';
                    echo '<input type="hidden" name="file_path" value="' . esc_attr($file_path) . '">';
                    echo '<input type="hidden" name="line_num" value="' . esc_attr($line_num) . '">';
                    echo '<input type="submit" name="remove_code" class="button button-secondary" value="Remover código malicioso">';
                    echo '</form>';
                    break;
                }
            }
        }

        // Se o código foi encontrado e o usuário deseja remover
        if ($found && isset($_POST['remove_code']) && $_POST['file_path'] === $file_path) {
            $this->remove_malicious_code($file_path, intval($_POST['line_num']));
        }
    }

    private function remove_malicious_code($file_path, $line_num) {
        $file_contents = file($file_path);
        
        if (isset($file_contents[$line_num])) {
            // Remove a linha maliciosa
            unset($file_contents[$line_num]);
            
            // Salva o arquivo sem a linha maliciosa
            file_put_contents($file_path, implode("", $file_contents));
            
            // Log de depuração
            error_log("Código malicioso removido do arquivo: $file_path na linha: $line_num");

            echo "<p>Código malicioso removido do arquivo <strong>{$file_path}</strong> na linha <strong>" . ($line_num + 1) . "</strong>.</p>";
        } else {
            echo "<p>Erro: Linha não encontrada para remoção.</p>";
            error_log("Erro: Linha $line_num não encontrada no arquivo $file_path para remoção.");
        }
    }
}

new BemSeiSecurityScanner();
