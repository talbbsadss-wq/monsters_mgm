<?php
require_once 'config.php';

// إدارة الكوكيز لجهاز الطالب لحفظ السجل الخاص به
if(!isset($_COOKIE['student_device_id'])) {
    $device_id = uniqid('ahmed_eng_', true);
    setcookie('student_device_id', $device_id, time() + (86400 * 30 * 12), "/"); 
} else {
    $device_id = $_COOKIE['student_device_id'];
}

$error = "";
$success_msg = "";

// 1. معالجة تسجيل الدخول
if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if ($username === "الاستاذ احمد عدنان" && $password === "سادس اعدادي") {
        $_SESSION['role'] = 'teacher';
        $_SESSION['name'] = 'الأستاذ أحمد عدنان';
        header("Location: index.php");
        exit;
    } else {
        if (!empty($username) && !empty($password)) {
            $_SESSION['role'] = 'student';
            $_SESSION['name'] = $username;
            header("Location: index.php");
            exit;
        } else {
            $error = "الرجاء إدخال الاسم الثلاثي والرمز السري!";
        }
    }
}

// 2. تسجيل الخروج
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}

// 3. لوحة الأستاذ: إضافة سؤال جديد
if (isset($_POST['add_question']) && isset($_SESSION['role']) && $_SESSION['role'] === 'teacher') {
    $question = $conn->real_escape_string($_POST['question']);
    $a = $conn->real_escape_string($_POST['option_a']);
    $b = $conn->real_escape_string($_POST['option_b']);
    $c = $conn->real_escape_string($_POST['option_c']);
    $d = $conn->real_escape_string($_POST['option_d']);
    $correct = $conn->real_escape_string($_POST['correct_option']);
    $hint = $conn->real_escape_string($_POST['hint']);

    $sql = "INSERT INTO quizzes (question, option_a, option_b, option_c, option_d, correct_option, hint) 
            VALUES ('$question', '$a', '$b', '$c', '$d', '$correct', '$hint')";
    if ($conn->query($sql)) {
        $success_msg = "تم بنجاح إرفاق الأسئلة ونشرها للطلاب فوراً!";
    } else {
        $error = "حدث خطأ أثناء حفظ السؤال في قاعدة البيانات.";
    }
}

// 4. لوحة الطالب: إرسال الإجابات
if (isset($_POST['submit_exam']) && isset($_SESSION['role']) && $_SESSION['role'] === 'student') {
    $answers = isset($_POST['answers']) ? $_POST['answers'] : [];
    $score = 0;
    $total = 0;
    
    $quiz_query = $conn->query("SELECT * FROM quizzes");
    while ($q = $quiz_query->fetch_assoc()) {
        $total++;
        if (isset($answers[$q['id']]) && $answers[$q['id']] === $q['correct_option']) {
            $score++;
        }
    }
    
    if ($total > 0) {
        $student_name = $_SESSION['name'];
        $conn->query("INSERT INTO student_results (student_name, device_id, score, total_questions) VALUES ('$student_name', '$device_id', '$score', '$total')");
        header("Location: index.php?exam_finished=1");
        exit;
    } else {
        $error = "لا توجد أسئلة لتقديمها في هذا الاختبار!";
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>منصة الأستاذ أحمد عدنان للغة الإنكليزية</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">
    <header class="main-header">
        <h1>منصة الأستاذ أحمد عدنان التعليمية للغة الإنكليزية</h1>
        <?php if(isset($_SESSION['role'])): ?>
            <div class="user-info">
                <span>مرحباً بك: <strong><?php echo htmlspecialchars($_SESSION['name']); ?></strong></span>
                <a href="?logout=1" class="logout-link">تسجيل الخروج ⬅</a>
            </div>
        <?php endif; ?>
    </header>

    <?php if($error): ?> <div class="alert alert-error"><?php echo $error; ?></div> <?php endif; ?>
    <?php if($success_msg): ?> <div class="alert alert-success"><?php echo $success_msg; ?></div> <?php endif; ?>

    <?php if(!isset($_SESSION['role'])): ?>
        <div class="card login-card animate-fade">
            <h2>تسجيل دخول الطلبة والأساتذة</h2>
            <p class="login-subtitle">يرجى إدخال البيانات المعتمدة للوصول إلى لوحة الاختبارات</p>
            <form action="" method="POST">
                <div class="form-group">
                    <label>الاسم الثلاثي للطالب (أو اسم المدرس):</label>
                    <input type="text" name="username" required placeholder="مثال: محمد جاسم محمد...">
                </div>
                <div class="form-group">
                    <label>الرمز السري:</label>
                    <input type="password" name="password" required placeholder="أدخل الرمز الخاص بك...">
                </div>
                <button type="submit" name="login" class="btn btn-primary">دخول للمنصة</button>
            </form>
        </div>

    <?php elseif($_SESSION['role'] === 'teacher'): ?>
        <div class="card teacher-card animate-slide">
            <div class="card-header-accent">
                <h2>إضافة اختبار جديد وإرفاق الأسئلة</h2>
                <span class="badge">صلاحيات المدرس</span>
            </div>
            <form action="" method="POST">
                <div class="form-group">
                    <label>نص السؤال الوزاري أو الإثرائي:</label>
                    <textarea name="question" required placeholder="اكتب السؤال هنا بشكل واضح..."></textarea>
                </div>
                <div class="options-layout">
                    <div class="form-group"><label>خيار (A):</label><input type="text" name="option_a" required placeholder="الخيار الأول"></div>
                    <div class="form-group"><label>خيار (B):</label><input type="text" name="option_b" required placeholder="الخيار الثاني"></div>
                    <div class="form-group"><label>خيار (C):</label><input type="text" name="option_c" required placeholder="الخيار الثالث"></div>
                    <div class="form-group"><label>خيار (D):</label><input type="text" name="option_d" required placeholder="الخيار الرابع"></div>
                </div>
                <div class="form-group-row">
                    <div class="form-group select-group">
                        <label>الجواب الصحيح المعتمد:</label>
                        <select name="correct_option" required>
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                            <option value="D">D</option>
                        </select>
                    </div>
                    <div class="form-group hint-group">
                        <label>تلميح ذكي (Hint) يظهر للطالب عند الخطأ:</label>
                        <input type="text" name="hint" placeholder="مثال: انتبه إلى الدلالة الزمنية للجملة...">
                    </div>
                </div>
                <button type="submit" name="add_question" class="btn btn-success">⚡ ارفاق الاسئلة ونشرها للطلاب فوراً</button>
            </form>
        </div>

        <div class="card results-card animate-slide" style="margin-top: 25px;">
            <h2>📊 جدول إشعارات ونتائج الطلاب المستلمة</h2>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>الاسم الثلاثي للطالب</th>
                            <th>النتيجة المكتسبة</th>
                            <th>تاريخ ووقت الاختبار</th>
                            <th>حالة الإجابة وطبيعتها</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $results = $conn->query("SELECT * FROM student_results ORDER BY exam_date DESC");
                        if($results && $results->num_rows > 0) {
                            while($row = $results->fetch_assoc()) {
                                $percentage = $row['total_questions'] > 0 ? ($row['score'] / $row['total_questions']) * 100 : 0;
                                $is_passed = $percentage >= 50;
                                echo "<tr>
                                    <td><span class='student-tag'>👤 {$row['student_name']}</span></td>
                                    <td><strong class='score-highlight'>{$row['score']} / {$row['total_questions']}</strong></td>
                                    <td><span class='date-text'>{$row['exam_date']}</span></td>
                                    <td><span class='status-badge ".($is_passed ? 'badge-pass' : 'badge-fail')."'>".($is_passed ? '✔ إجابة صحيحة وممتازة' : '❌ إجابة ضعيفة وتحتاج مراجعة')."</span></td>
                                </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='4' class='empty-table'>لم يقم أي طالب بتقديم الامتحان حتى الآن.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php elseif($_SESSION['role'] === 'student'): ?>
        <?php if(isset($_GET['exam_finished'])): ?>
            <div class="card text-center success-screen pop-effect">
                <div class="success-icon">🎉</div>
                <h2>تم إرسال إجاباتك بنجاح للأستاذ أحمد عدنان!</h2>
                <p>تم إرسال إشعار فوري إلى لوحة تحكم المدرس متضمناً اسمك الثلاثي لتقييم الإجابات.</p>
                <a href="index.php" class="btn btn-primary" style="max-width: 250px; margin: 20px auto 0;">العودة إلى شاشة الاختبارات</a>
            </div>
        <?php else: ?>
            <div class="card exam-card animate-slide">
                <div class="student-exam-header">
                    <h2>📋 قائمة الامتحانات الإنكليزية المتاحة</h2>
                    <button onclick="window.location.reload();" class="btn-refresh">🔄 البحث عن الامتحان (تحديث الصفحة)</button>
                </div>

                <form action="" method="POST" id="examForm">
                    <?php
                    $quizzes = $conn->query("SELECT * FROM quizzes ORDER BY id DESC");
                    if($quizzes && $quizzes->num_rows > 0):
                        $count = 1;
                        while($q = $quizzes->fetch_assoc()):
                    ?>
                        <div class="quiz-item-block" data-correct="<?php echo $q['correct_option']; ?>" data-hint="<?php echo htmlspecialchars($q['hint']); ?>">
                            <h3>سؤال رقم (<?php echo $count++; ?>): <?php echo htmlspecialchars($q['question']); ?></h3>
                            <div class="options-wrapper">
                                <label class="option-label"><input type="radio" name="answers[<?php echo $q['id']; ?>]" value="A" required onchange="validateStudentAnswer(this)"> <span><strong>A)</strong> <?php echo htmlspecialchars($q['option_a']); ?></span></label>
                                <label class="option-label"><input type="radio" name="answers[<?php echo $q['id']; ?>]" value="B" onchange="validateStudentAnswer(this)"> <span><strong>B)</strong> <?php echo htmlspecialchars($q['option_b']); ?></span></label>
                                <label class="option-label"><input type="radio" name="answers[<?php echo $q['id']; ?>]" value="C" onchange="validateStudentAnswer(this)"> <span><strong>C)</strong> <?php echo htmlspecialchars($q['option_c']); ?></span></label>
                                <label class="option-label"><input type="radio" name="answers[<?php echo $q['id']; ?>]" value="D" onchange="validateStudentAnswer(this)"> <span><strong>D)</strong> <?php echo htmlspecialchars($q['option_d']); ?></span></label>
                            </div>
                            <div class="live-hint-box" style="display:none;"></div>
                        </div>
                    <?php 
                        endwhile;
                        echo '<button type="submit" name="submit_exam" class="btn btn-primary submit-exam-btn">✔ إنهاء الامتحان وإرسال الإجابات للأستاذ</button>';
                    else:
                        echo '<div class="no-exams"><p>لا توجد أسئلة مرفقة حالياً من قبل الأستاذ أحمد.</p><p class="sub">يرجى الضغط على زر <strong>"البحث عن الامتحان"</strong> بالأعلى لعمل ريفرش للصفحة.</p></div>';
                    endif;
                    ?>
                </form>
            </div>

            <div class="card history-card animate-slide" style="margin-top: 25px;">
                <h2>📜 الـ History: سجل إجاباتك ودرجاتك السابقة على هذا الجهاز</h2>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>تاريخ تقديم الاختبار</th>
                                <th>النتيجة الكلية المحققة</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $my_results = $conn->query("SELECT * FROM student_results WHERE device_id = '$device_id' ORDER BY exam_date DESC");
                            if($my_results && $my_results->num_rows > 0) {
                                while($res = $my_results->fetch_assoc()) {
                                    echo "<tr>
                                        <td><span class='date-text'>📅 {$res['exam_date']}</span></td>
                                        <td><span class='my-score-badge'>{$res['score']} من أصل {$res['total_questions']}</span></td>
                                    </tr>";
                                }
                            } else {
                                echo "<tr><td colspan='2' class='empty-table'>لم تقم بإجراء أي امتحانات سابقة من هذا المتصفح حتى الآن.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
function validateStudentAnswer(radioInput) {
    const parentBlock = radioInput.closest('.quiz-item-block');
    const correctAnswer = parentBlock.getAttribute('data-correct');
    const hintMessage = parentBlock.getAttribute('data-hint');
    const liveHintDiv = parentBlock.querySelector('.live-hint-box');
    
    const labels = parentBlock.querySelectorAll('.option-label');
    labels.forEach(l => l.classList.remove('wrong-selected', 'correct-selected'));

    if (radioInput.value !== correctAnswer) {
        parentBlock.style.borderRight = "6px solid #e74c3c";
        radioInput.closest('.option-label').classList.add('wrong-selected');
        
        if(hintMessage.trim() !== "") {
            liveHintDiv.style.display = "block";
            liveHintDiv.innerHTML = "💡 <strong>مساعدة (Hint):</strong> " + hintMessage;
            liveHintDiv.className = "live-hint-box animate-pop";
        } else {
            liveHintDiv.style.display = "none";
        }
    } else {
        parentBlock.style.borderRight = "6px solid #2ecc71";
        radioInput.closest('.option-label').classList.add('correct-selected');
        liveHintDiv.style.display = "none";
    }
}
</script>
</body>
</html>
