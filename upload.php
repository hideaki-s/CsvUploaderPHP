<?php
/* URI/DIR設定
 * ------------------------------------------------------------- */
define('_URI_SSL_'  , 'https://'.$_SERVER['HTTP_HOST'].'/');
define('_URI_DIR_'  , '/');
define('__ABSPATH__', dirname(__FILE__) . '/');

// Definition
$error = "";
$success = "";
// ファイルの保存先
$upload_path = __ABSPATH__.'tmp/data/';
$file_max_size = 2000000; // 2MB

// ①POSTリクエストによるページ遷移かチェック
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  if ( !isset($_FILES['upload']['error']) || !is_int($_FILES['upload']['error']) ) {
    $error .= 'パラメータが不正です。<br>\n';
  }

  // $_FILES['upfile']['error'] の値を確認
  switch ( $_FILES['upfile']['error'] ) {
    case UPLOAD_ERR_OK: // OK
      break;
    case UPLOAD_ERR_NO_FILE:   // ファイル未選択
      $error .= "ファイルが選択されていません。<br>\n";
      break;
    case UPLOAD_ERR_INI_SIZE:  // php.ini定義の最大サイズ超過
    case UPLOAD_ERR_FORM_SIZE: // フォーム定義の最大サイズ超過 (設定した場合のみ)
      $error .= "ファイルサイズが大きすぎます。<br>\n";
      break;
    default:
      $error .= "その他のエラーが発生しました。<br>\n";
      break;
  }

  // ここで定義するサイズゼロチェック
  if ( $_FILES['upfile']['size'] === 0) {
    $error .= "ファイルが選択されていません。<br>\n";
  }

  // ここで定義するサイズ上限のオーバーチェック
  if ( $_FILES['upfile']['size'] > $file_max_size ) {
    $error .= "ファイルサイズが大きすぎます。<br>\n";
  }

  // $_FILES['upfile']['mime']の値はブラウザ側で偽装可能なので
  // MIMEタイプに対応する拡張子を自前で取得する
  if ( $_FILES['upfile']['type'] != 'text/csv' ) {
    $error .= "ファイル形式が不正です。<br>\n";
  }

  // TODO 一時ファイルであればわざわざコピーせずとも内容を読み取ってしまえばいい気がする
/*
  // ファイルデータからSHA-1ハッシュを取ってファイル名を決定し，保存する
  $path = sprintf($upload_path. date("YmdHis").'_%s.%s', sha1_file($_FILES['upload']['tmp_name']), 'csv');
  if (move_uploaded_file($_FILES['upload']['tmp_name'], $path)) {
    chmod($path, 0644);
    $success = 'ファイルは正常にアップロードされました。<br>\n';
  }
  else {
    $error .= 'ファイル保存時にエラーが発生しました。<br>\n';
  }

  print($success);
*/

  // CSVデータ変換と読込み
  setlocale(LC_ALL,'jp_JP.UTF-8');
  $target_path = $_FILES['upload']['tmp_name'];
  $data = file_get_contents($target_path);
  $data = mb_convert_encoding($data,'UTF-8','sjis-win');

  $temp = tmpfile();
  $csv  = array();

  fwrite($temp, $data);
  rewind($temp);
  // CSVデータ抽出
  while (($data = fgetcsv($temp, 0, ",")) !== FALSE) {
    $csv[] = $data;
  }
  fclose($temp);

  // CSVデータ辞書化
  $records = array();
  foreach ($csv as $i => $row)
  {
    // 1行目はキーヘッダ行として取り込み
    if($i===0) {
      foreach($row as $j => $col) $colbook[$j] = $col;
      continue;
    }

    // 2行目以降はデータ行として取り込み
    $line = array();
    foreach($colbook as $j=>$col) $line[$colbook[$j]] = @$row[$j];
    $records[] = $line;
  }

  // 出力
  echo "<pre>";
  var_dump($records);
  echo "</pre>";

// POSTリクエストによる遷移じゃない場合
}
else {

  $error .= '不正なアクセスです。<br>\n';

}
