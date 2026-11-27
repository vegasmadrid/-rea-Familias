<?php
/**
 * Textos legales e informativos de la matrícula (transcritos de las hojas en papel):
 * - "Información del centro a la familia" (apartados A–G).
 * - "Manifestaciones de los padres/tutores" (1º–6º).
 * - "Información protección de datos alumnos – Ficha Matrícula" (Fundación Casa de la Virgen).
 *
 * The first and the last can be edited in Área Familias › Nuevas familias; the
 * manifestations are fixed because each one is a question of the form.
 *
 * Simple format of the texts (one paragraph per line):
 *   ## Título        heading
 *   - texto          item of a list
 *   -- texto         sub-item (printed with a dash, never a box)
 *   **negrita**      bold
 *   [CONSENTIMIENTO_IMAGENES] / [CONSENTIMIENTO_AMPA]   where the "Consiento / No consiento" boxes go
 */

if (!defined('ABSPATH')) {
    exit;
}

class AF_Matricula_Textos {

    /** Consent boxes of the data-protection sheet: marker => form field and label. */
    public static function consentimientos() {
        return array(
            'CONSENTIMIENTO_IMAGENES' => array('campo' => 'rgpd_imagenes', 'label' => __('Publicación de imágenes y nombres de los alumnos (apartados e y f)', 'area-familias')),
            'CONSENTIMIENTO_AMPA'     => array('campo' => 'rgpd_ampa', 'label' => __('Cesión de datos al AMPA (apartado 3)', 'area-familias')),
        );
    }

    public static function info_centro() {
        return <<<'TXT'
## A) Centro privado católico.
El Colegio Casa de la Virgen es un centro privado y confesional católico, estando definida su oferta educativa en el carácter propio y proyecto educativo, documentos que tienen derecho a conocer todos los miembros de la comunidad educativa del centro y que, previa petición, están a disposición de la familia en la Secretaría del centro y la página web www.casadelavirgen.es
## B) Concierto educativo.
El centro, en el presente curso escolar, está concertado con la Consejería de Educación, en las enseñanzas de 2º ciclo de Educación Infantil, Educación Primaria y ESO. El 1er Ciclo de Infantil y el Bachillerato son privados.
Los centros concertados están sometidos a las normas reguladoras del régimen de conciertos, entre otras, los artículos 116 y 117 de la Ley Orgánica 8/2013, de 9 de diciembre, de mejora de la educación.
## C) Normas de organización.
Las normas de organización, funcionamiento y convivencia en el Centro están contenidas en el Reglamento de Régimen Interior del centro y normas de desarrollo que son públicas y todos los miembros de la comunidad educativa afectados por ellas tienen derecho a conocer. Su desconocimiento no exime de su responsabilidad. Ambos documentos son descargables en formato PDF de la página web del colegio www.casadelavirgen.es, dichas normas de convivencia han de ser cumplidas y respetadas por todo la comunidad educativa.
## D) Actividades y Servicios.
Las actividades escolares complementarias, las actividades extraescolares y extracurriculares y los servicios complementarios organizados por el centro tienen **carácter voluntario y no lucrativo**.
El centro organiza una oferta de actividades escolares complementarias y servicios complementarios para el conjunto de los alumnos de cada grupo, como parte de la oferta y servicio educativo vinculado a su carácter propio, a su Proyecto Educativo, a la mejora de la calidad de la enseñanza y la formación integral de los alumnos/as.
La participación de los alumnos/as en estas actividades y servicios, con carácter voluntario, constituye un complemento fundamental para su formación y el servicio educativo del centro. La concreción de las actividades para cada curso escolar se realiza por el Consejo escolar del centro.
## E) Información y entrevistas.
Los padres de los alumnos/as serán informados de los aspectos relacionados con la educación de sus hijos o de las cuestiones del centro que les afecten según la naturaleza del tema, mediante aviso personal o comunicación general por los procedimientos que en cada caso determinen (tablón de anuncios, circulares trasladadas por alumnos, teléfono, correo electrónico, comunidades, correo postal, etc.).
Asimismo, los padres podrán entrevistarse con la dirección, los tutores, los orientadores o los profesores de sus hijos/as en el horario establecido al efecto, y previa petición de hora.
## F) Plazo de matriculación.
La inscripción en el centro se produce por un curso escolar. El centro remitirá a las familias la hoja de renovación de matrícula la cual deben devolver firmada en las fechas que indiquen oportunamente. En caso de causar baja antes de la finalización del curso deberán firmar en la secretaría del centro el documento previsto al efecto.
## G) Incorporación al Centro del alumno solicitante de plaza.
La incorporación de su hijo/a y de Vds. a este centro y a su comunidad educativa, como fruto de su derecho de elección de centro y de tipo de educación que desea para su hijo/a comporta unos compromisos recíprocos.
Por ello, el centro se compromete a desarrollar el proceso educativo en un clima de acogida, atención individualizada, formación integral, respeto a los derechos de los alumnos/as, todo ello con nuestra propuesta educativa.
TXT;
    }

    /**
     * Manifestations: key => number, text and type of answer.
     * tipo: acepta (required box) | eleccion (yes/no) | texto (optional).
     */
    public static function manifestaciones() {
        return array(
            'm1' => array('n' => '1º', 'tipo' => 'acepta', 'texto' => 'Que conocemos y respetamos íntegramente el carácter propio del centro que define su identidad, el Proyecto Educativo y el Reglamento de Régimen Interior, que hemos podido consultar en la secretaría del centro y aceptamos que nuestro hijo/a sea educado conforme al estilo de educación definido en dichos documentos.'),
            'm2' => array('n' => '2º', 'tipo' => 'acepta', 'texto' => "Que, como primeros responsables de la educación de nuestros hijo/a participaremos activamente en el proceso educativo de nuestros hijo/a en el ámbito escolar:\n- Adoptando las medidas que sean necesarias para asistir a clase con puntualidad.\n- Interesándonos por conocer y apoyar la evolución del proceso educativo de nuestro hijo/a.\n- Estimulando a nuestro hijo/a para que lleve a cabo las tareas y actividades de estudio que se le encomienden.\n- Participando en las entrevistas y reuniones a las que seamos convocados por el centro en relación con el proceso educativo de nuestro hijo/a.\n- Informando a los responsables del centro que, en cada caso corresponda, de aquellos aspectos de la personalidad y circunstancias de nuestro hijo/a que afecten o sean relevantes para su formación y su relación con el centro y los miembros de la comunidad educativa.\n- Respetando y haciendo respetar las normas de convivencia del centro y fomentando el respeto por todos los componentes de la comunidad educativa. El respeto a las normas de convivencia del centro que afecta a la indumentaria o exhibición de complementos que puedan entrar en contradicción con el ideario del centro."),
            'm3' => array('n' => '3º', 'tipo' => 'eleccion', 'si' => __('Aceptamos la participación', 'area-familias'), 'no' => __('No la aceptamos (manifestación en contrario)', 'area-familias'), 'texto' => 'Que, salvo manifestación en contrario, aceptamos la participación de nuestro hijo/a en las actividades escolares complementarias y en los servicios complementarios organizados por el centro para el conjunto de los alumnos de su grupo, nivel o centro, conforme a los precios que se establezcan y sean autorizados a tenor de la legislación vigente, de los que seremos informados puntualmente, todo ello, sin perjuicio de la posible inscripción en otros servicios complementarios extraescolares que ofrezca el centro.'),
            'm4' => array('n' => '4º', 'tipo' => 'eleccion', 'si' => __('Autorizamos', 'area-familias'), 'no' => __('No autorizamos', 'area-familias'), 'texto' => 'Que los que suscriben el presente documento, en su calidad de representantes legales del menor escolarizado en el centro, autorizan para que obtenga y utilice imágenes y el nombre del menor, siempre relacionadas con la actividad del mismo, en las publicaciones de todo tipo que éste realice, ya sean impresas o en formato digital (agenda escolar, orla, exposiciones, pagina web del centro, vídeos promocionales, redes sociales, AMPAS, etc). Igualmente autorizamos a que las imágenes sean obtenidas y difundidas en condiciones de gratuidad por los medios de comunicación siempre que dicha difusión no comporte una intromisión ilegítima en la intimidad, honra o reputación del menor o sea contraria a sus intereses, todo ello en los términos establecidos en el artículo 4 número 3 de la ley Orgánica 1/1996, de Protección Jurídica del Menor.'),
            'm5' => array('n' => '5º', 'tipo' => 'acepta', 'texto' => 'Que los datos e información que aportamos son veraces y completos, sin que hayamos omitido datos relevantes o que hayan de ser distintos en cuenta en relación con la incorporación al centro de nuestro hijo/a.'),
            'm6' => array('n' => '6º', 'tipo' => 'texto', 'texto' => 'Otras manifestaciones:'),
        );
    }

    public static function proteccion() {
        return <<<'TXT'
## 2.3. INFORMACIÓN PROTECCIÓN DE DATOS ALUMNOS – Ficha Matrícula
**Responsable del Tratamiento:** FUNDACIÓN CASA DE LA VIRGEN.
**Fin del tratamiento:**
- a) Gestionar el proceso de matriculación; el control y la gestión administrativa (solicitud de títulos, solicitud de becas, traslados de centro, seguro escolar, garantía de identificación, acreditación y control de entrega de documentación, etc.), control de la ruta escolar de los mismos, control de asistencia, facturación y comunicaciones no comerciales.
- b) Atención, evaluación y seguimiento académico del alumno.
- c) Correcta atención al alumno en relación con los datos de salud facilitados.
- d) Toma de imágenes para uso interno (incluida la elaboración de material pedagógico).
- e) Publicación de imágenes de los alumnos tomadas en diferentes actos y eventos (incluidas actividades extraescolares y salidas) y/o sus nombres y apellidos con fines informativos o publicitarios siempre que dicha difusión no suponga ni pueda implicar menoscabo de su honra o reputación o que sea contraria a sus intereses ni intromisión ilegítima a la intimidad o reputación del alumno en:
-- la página web, así como como en YouTube, Twitter, Facebook, Instagram, y/o en otras redes sociales, blogs
-- orlas y reportajes
-- anuarios, libros conmemorativos y/o revistas del centro.
- f) Publicación de imágenes de los alumnos tomadas en diferentes actos y eventos (incluidas actividades extraescolares y salidas) y/o sus nombres y apellidos en la página web, en reportajes, anuarios, blogs y/o revistas del centro, así como en libros conmemorativos, u cualquier otro uso dentro del contexto escolar y obtenidas y difundidas por los medios de comunicación, así como como en YouTube, TikTok, Facebook, Instagram, y/o en otras redes sociales y otros medios de comunicación externos, con fines informativos o publicitarios siempre que dicha difusión no suponga ni pueda implicar menoscabo de su honra o reputación o que sea contraria a sus intereses ni intromisión ilegítima a la intimidad o reputación del alumno.
[CONSENTIMIENTO_IMAGENES]
**Legitimación del tratamiento:** Ley educativa vigente para el tratamiento de los datos en el ejercicio de la función educativa. También están legitimados para el desarrollo y ejecución de la relación jurídica que se produce con la matriculación del alumno, así como por el consentimiento del interesado.
**Comunicación de los datos:** Se realizarán las siguientes cesiones:
- 1. Las previstas por ley y precisas para el desempeño de sus atribuciones y cumplimiento de su objeto social como las realizadas ante la Consejería de Educación para la emisión de títulos oficiales, la comunicación de datos contenidos en la PGA, traslados de expedientes y a la Consejería de Sanidad para la participación en programas de vacunación.
- 2. A Bancos y Cajas de Ahorro para la domiciliación de los correspondientes recibos, siempre que haya cumplimentado el apartado de "Domiciliación Bancaria" y/o documento correspondiente para este fin.
- 3. Al AMPA para la gestión de actividades y clase extraescolares y para la gestión del cobro de la cuota.
[CONSENTIMIENTO_AMPA]
Podrán tener acceso a sus datos aquellos proveedores que nos prestan un servicio en calidad de encargados de tratamiento y con los que tenemos suscritos los correspondientes contratos.
FUNDACIÓN CASA DE LA VIRGEN hace uso de Google Drive por lo cual los datos pueden estar almacenados, procesados y transmitidos a Estados Unidos y ubicaciones de todo el mundo. Google cuenta con Adendas adaptadas a la normativa de RGPD a firmar con sus clientes para regular la relación.
**Conservación de los datos:**
- a) Datos académicos: mientras exista un interés mutuo para mantener el fin del tratamiento.
- b) Datos facturación: el tiempo legalmente establecido.
- b) Datos de salud: cuando el alumno cause baja.
- c) Datos de contacto: hasta que el alumno cause baja.
- d) Imágenes: hasta que el alumno revoque su consentimiento o deje de ser de interés para el centro.
**Derechos que asisten al Interesado:** - Derecho a retirar el consentimiento en cualquier momento. - Derecho de acceso, rectificación, portabilidad y supresión de sus datos y a la limitación u oposición a su tratamiento, así como a no ser objeto de decisiones basadas únicamente en el tratamiento automatizado de sus datos - Derecho a presentar una reclamación ante la Autoridad de control española (www.aepd.es) si considera que el tratamiento no se ajusta a la normativa vigente.
**Datos de contacto para ejercer sus derechos:** FUNDACIÓN CASA DE LA VIRGEN, Calle Virgen del Val,1, 28027, Madrid o a través de correo electrónico a fundacion@casadelavirgen.es, junto con prueba válida en derecho, como fotocopia del D.N.I. e indicando en el asunto "PROTECCIÓN DE DATOS".
**Información adicional:** En la política de privacidad de nuestra web https://madrid.casadelavirgen.es/.
*AVISO: El Centro es el único autorizado para la toma y publicación de imágenes. Esta prohibida la realización de fotografías y videos por personal no autorizado. Por tanto, el Centro no se hace responsable de la difusión o uso indebido que se hagan de imágenes no autorizadas.*
TXT;
    }

    /** Markers present in a text ("CONSENTIMIENTO_IMAGENES"…). */
    public static function marcas($texto) {
        preg_match_all('/^\s*\[([A-Z_]+)\]\s*$/m', (string) $texto, $m);
        return array_values(array_intersect($m[1], array_keys(self::consentimientos())));
    }

    /**
     * Text → HTML. $marca(string $marker): HTML that replaces a [MARKER] line.
     */
    public static function html($texto, $marca = null) {
        $out = '';
        $lista = false;
        $inline = function ($s) {
            $s = esc_html($s);
            $s = preg_replace('/\*\*(.+?)\*\*/u', '<strong>$1</strong>', $s);
            $s = preg_replace('/(?<![*\w])\*(?!\s)(.+?)(?<!\s)\*(?![*\w])/u', '<em>$1</em>', $s);
            return $s;
        };
        foreach (preg_split('/\r\n|\r|\n/', (string) $texto) as $l) {
            $l = trim($l);
            $es_sub = strpos($l, '-- ') === 0;
            $es_item = $es_sub || strpos($l, '- ') === 0;
            if ($lista && !$es_item) {
                $out .= '</ul>';
                $lista = false;
            }
            if ($l === '') {
                continue;
            }
            if (preg_match('/^\[([A-Z_]+)\]$/', $l, $m)) {
                $out .= $marca ? call_user_func($marca, $m[1]) : '';
            } elseif (strpos($l, '## ') === 0) {
                $out .= '<h4>' . $inline(substr($l, 3)) . '</h4>';
            } elseif ($es_item) {
                if (!$lista) {
                    $out .= '<ul>';
                    $lista = true;
                }
                $out .= $es_sub ? '<li class="sub">– ' . $inline(substr($l, 3)) . '</li>' : '<li>' . $inline(substr($l, 2)) . '</li>';
            } else {
                $out .= '<p>' . $inline($l) . '</p>';
            }
        }
        return $out . ($lista ? '</ul>' : '');
    }
}
