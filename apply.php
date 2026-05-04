<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enrollment Application | Pines NHS</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .form-section { 
            padding: 25px; 
            margin-bottom: 20px; 
            text-align: left; 
        }

        .form-grid { 
            display: grid; 
            grid-template-columns: 1fr 1fr; 
            gap: 15px; 
            margin-top: 15px; 
        }

        .form-group { 
            display: flex; 
            flex-direction: column; 
            gap: 5px; 
        }

        label { 
            font-size: 0.9rem; 
            font-weight: 500; color: 
            var(--text-main); 
        }

        input, select, textarea {
            width: 100%; padding: 10px; 
            border-radius: 8px;
            border: 2px solid;
            background: transparent; 
            color: var(--text-main);
        }

        input:focus, select:focus, textarea:focus { 
            outline: 2px solid var(--primary-color); 
        }

        .full-width { 
            grid-column: 1 / -1; 
        }
        
        @media (max-width: 600px) { 
            .form-grid { 
                grid-template-columns: 1fr; 
            } 
        }
    </style>
</head>
<body style="align-items: center; padding: 40px 20px;">

    <div style="max-width: 700px; width: 100%;">
        <a href="index.php" class="btn btn-outline" style="padding: 5px 10px;">&larr; Back</a>
        
        <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 30px;">
            <div>
                <h2 style="font-size: 1.5rem;">Enrollment Application</h2>
                <p class="text-muted" style="font-size: 0.85rem;">S.Y. 2025-2026</p>
            </div>
        </div>

        <form action="actions/submit_application.php" method="POST">
            
            <div class="glass-panel form-section">
                <h3 style="font-size: 1.1rem; margin-bottom: 10px;">Personal Information</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label>First Name *</label><input type="text" name="first_name" required>
                    </div>
                    <div class="form-group">
                        <label>Middle Name</label><input type="text" name="middle_name">
                    </div>
                    <div class="form-group">
                        <label>Last Name *</label><input type="text" name="last_name" required>
                    </div>
                    <div class="form-group">
                        <label>Suffix</label><input type="text" name="suffix" placeholder="Jr., III, etc.">
                    </div>
                    <div class="form-group">
                        <label>Date of Birth</label><input type="date" name="date_of_birth">
                    </div>
                    <div class="form-group">
                        <label>Gender</label>
                        <select name="gender">
                            <option value="">Select</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="glass-panel form-section">
                <h3 style="font-size: 1.1rem; margin-bottom: 10px;">Contact & Guardian</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Email Address *</label><input type="email" name="student_email" required>
                    </div>
                    <div class="form-group">
                        <label>Contact Number</label><input type="text" name="contact_number">
                    </div>
                    <div class="form-group full-width">
                        <label>Address</label><textarea name="address" rows="2"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Guardian / Parent Name</label><input type="text" name="guardian_name">
                    </div>
                    <div class="form-group">
                        <label>Guardian Contact Number</label><input type="text" name="guardian_contact">
                    </div>
                </div>
            </div>

            <div class="glass-panel form-section">
                <h3 style="font-size: 1.1rem; margin-bottom: 10px;">Academic Information</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Grade Level *</label>
                        <select name="grade_level" required>
                            <option value="">Select</option>
                            <option value="Grade 7">Grade 7</option>
                            <option value="Grade 8">Grade 8</option>
                            <option value="Grade 9">Grade 9</option>
                            <option value="Grade 10">Grade 10</option>
                            <option value="Grade 11">Grade 11</option>
                            <option value="Grade 12">Grade 12</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Strand (SHS only)</label>
                        <select name="strand">
                            <option value="N/A">N/A</option>
                            <option value="STEM">STEM</option>
                            <option value="HUMSS">HUMSS</option>
                            <option value="ABM">ABM</option>
                            <option value="GAS">GAS</option>
                            <option value="TVL-ICT">TVL-ICT</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Learner Reference Number (LRN)</label><input type="text" name="lrn">
                    </div>
                    <div class="form-group">
                        <label>Previous School</label><input type="text" name="previous_school">
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 15px; font-size: 1.1rem; margin-top: 10px;">
                Submit Application
            </button>
        </form>
    </div>

</body>
</html>