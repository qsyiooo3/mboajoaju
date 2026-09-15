<?php
// includes/upload.php - Manejo seguro de archivos

/**
 * Sube un archivo de manera segura
 * 
 * @param array $file Archivo $_FILES
 * @param string $targetDir Directorio destino
 * @param array|null $allowedExtensions Extensiones permitidas
 * @return array Resultado de la operación
 */
function uploadFile($file, $targetDir, $allowedExtensions = null) {
    if (!$allowedExtensions) {
        $allowedExtensions = ALLOWED_EXTENSIONS;
    }
    
    $errors = [];
    
    // Verificar error de subida
    if ($file['error'] !== UPLOAD_ERR_OK) {
        switch ($file['error']) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $errors[] = 'El archivo excede el tamaño máximo permitido.';
                break;
            case UPLOAD_ERR_PARTIAL:
                $errors[] = 'El archivo se subió parcialmente.';
                break;
            case UPLOAD_ERR_NO_FILE:
                $errors[] = 'No se seleccionó ningún archivo.';
                break;
            default:
                $errors[] = 'Error al subir el archivo.';
        }
        return ['success' => false, 'errors' => $errors];
    }
    
    $fileName = $file['name'];
    $fileSize = $file['size'];
    $fileTmp = $file['tmp_name'];
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    
    // Validar extensión
    if (!in_array($fileExt, $allowedExtensions)) {
        $errors[] = 'Tipo de archivo no permitido. Extensiones permitidas: ' . implode(', ', $allowedExtensions);
    }
    
    // Validar tamaño
    if ($fileSize > MAX_FILE_SIZE) {
        $errors[] = 'El archivo excede el tamaño máximo de ' . getFileSize(MAX_FILE_SIZE);
    }
    
    // Validar MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $fileTmp);
    finfo_close($finfo);
    
    if (!in_array($mimeType, ALLOWED_MIME_TYPES)) {
        $errors[] = 'El archivo parece ser de un tipo no permitido.';
    }
    
    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }
    
    // Generar nombre único
    $newFileName = uniqid() . '.' . $fileExt;
    $uploadPath = rtrim($targetDir, '/') . '/' . $newFileName;
    
    // Crear directorio si no existe
    if (!file_exists($targetDir)) {
        if (!mkdir($targetDir, 0755, true)) {
            $errors[] = 'No se pudo crear el directorio de destino.';
            return ['success' => false, 'errors' => $errors];
        }
    }
    
    // Mover el archivo
    if (move_uploaded_file($fileTmp, $uploadPath)) {
        // Establecer permisos
        chmod($uploadPath, 0644);
        
        return [
            'success' => true,
            'filename' => $newFileName,
            'original' => $fileName,
            'path' => $uploadPath,
            'size' => $fileSize,
            'mime_type' => $mimeType,
            'extension' => $fileExt
        ];
    } else {
        $errors[] = 'Error al mover el archivo al destino.';
        return ['success' => false, 'errors' => $errors];
    }
}

/**
 * Elimina un archivo de manera segura
 */
function deleteFile($filePath) {
    if (!file_exists($filePath)) {
        return false;
    }
    
    // Verificar que el archivo está dentro de uploads/
    $realPath = realpath($filePath);
    $uploadPath = realpath(UPLOAD_PATH);
    
    if ($realPath === false || strpos($realPath, $uploadPath) !== 0) {
        error_log("Intento de eliminar archivo fuera de uploads: $filePath");
        return false;
    }
    
    return unlink($filePath);
}

/**
 * Obtiene la URL de un archivo subido
 */
function getFileUrl($filename, $subfolder = '') {
    $base = URL_BASE . 'uploads/';
    if (!empty($subfolder)) {
        $base .= rtrim($subfolder, '/') . '/';
    }
    return $base . $filename;
}

/**
 * Limpia archivos temporales antiguos
 */
function cleanTempFiles($maxAge = 86400) { // 24 horas por defecto
    $tempDir = UPLOAD_PATH . 'temp/';
    if (!file_exists($tempDir)) return;
    
    $files = glob($tempDir . '*');
    $now = time();
    
    foreach ($files as $file) {
        if (is_file($file) && ($now - filemtime($file) > $maxAge)) {
            unlink($file);
        }
    }
}

/**
 * Verifica que el archivo sea una imagen válida
 */
function isValidImage($filePath) {
    if (!file_exists($filePath)) return false;
    
    $info = getimagesize($filePath);
    if (!$info) return false;
    
    $allowedTypes = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP, IMAGETYPE_BMP];
    return in_array($info[2], $allowedTypes);
}

/**
 * Genera un nombre de archivo único
 */
function generateUniqueFilename($originalName) {
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    return uniqid() . '.' . $ext;
}