<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>

<head>
    <link rel="stylesheet" href="../../css/browsing/default.css" type="text/css">
</head>

<body>
    <a name="top"></a>
    <div id="pagecontainer">
        <!-- testata -->
        <div id="header">
            <template_field class="microtemplate_field" name="header">header</template_field>
        </div>
        <!-- / testata -->
        <!-- menu -->
        <template_field class="microtemplate_field" name="adamenu">adamenu</template_field>
        <!-- / menu -->
        <!-- contenitore -->
        <div id="container">

            <!--dati utente-->
            <div id="status_bar">
                <div class="user_data_default status_bar">
                    <template_field class="microtemplate_field" name="user_data_micro">user_data_micro</template_field>
                    <span>
                        <template_field class="template_field" name="message">message</template_field>
                    </span>
                </div>
            </div>
			<!-- / dati utente -->

            <!-- contenuto -->

            <div id="content">
                <div id="contentcontent" class="contentcontent_default">
                    <!-- start tre blocchi grafici homepage -->
                    <h1 class="ui red header">
                        <template_field class="template_field" name="course_title">course_title</template_field>
                    </h1>
                    <div class="ui divider"></div>

                    <div class="ui three column stackable grid">

                        <div class="equal height row">
                            <div class="firstcol column">
                                <h3 class="ui header">
                                    <i class="info large icon"></i>
                                    <template_field class="template_field" name="firstcol_title">firstcol_title</template_field>
                                </h3>
                                <div class="ui large list">
                                    <div class="ui item">
                                        <template_field class="template_field" name="course_description">course_description</template_field>
                                    </div>
                                </div>
                            </div>

                            <div class="secondcol column">
                                <h3 class="ui header">
                                    <i class="book large icon"></i>
                                    <i18n>il corso</i18n>
                                </h3>
                                <div class="ui large list">
                                    <div class="item">
                                        <i class="angle right icon"></i>
                                        <template_field class="template_field" name="gostart">gostart</template_field>
                                    </div>
                                    <div class="item">
                                        <i class="double angle right icon"></i>
                                        <template_field class="template_field" name="gocontinue">gocontinue</template_field>
                                    </div>
                                    <div class="item">
                                        <i class="sitemap icon"></i>
                                        <template_field class="template_field" name="goindex">goindex</template_field>
                                    </div>
                                    <div class="item">
                                        <i class="users icon"></i>
                                        <template_field class="template_field" name="goforum">goforum</template_field>
                                    </div>
                                    <div class="item">
                                        <i class="time basic icon"></i>
                                        <template_field class="template_field" name="gohistory">gohistory</template_field>
                                    </div>
                                    <template_field class="template_field" name="badgesLink">badgesLink</template_field>
                                </div>
                            </div>

                            <div class="thirdcol column">
                                <h3 class="ui header">
                                    <i class="ok circle large icon"></i>
                                    <i18n>Stato</i18n>
                                </h3>
                                <template_field class="template_field" name="studentsOfInstance">studentsOfInstance</template_field>
                                <div class="ui large list">
                                    <template_field class="template_field" name="completeSummary">completeSummary</template_field>
                                    <div class="item">
                                        <i class="ok circle icon"></i>
                                        <span class="lastvisit label">
								    	<!-- <i18n>ultimo accesso</i18n>: -->
									</span>
								    	<template_field class="template_field" name="last_visit">last_visit</template_field>
                                    </div>
                                    <div class="item">
                                        <i class="ok circle icon"></i>
                                        <span class="enddate label">
								    	<i18n>Il corso termina il</i18n>:
								    </span>
                                        <strong>
								    	<template_field class="template_field" name="enddate">enddate</template_field>
								    </strong>
                                    </div>
                                    <div class="item">
                                        <i class="ok circle icon"></i>
                                        <span class="subscription label">
								    	<i18n>Stato Iscrizione</i18n>:
								    </span>
                                        <strong>
								    	<template_field class="template_field" name="subscription_status">subscription_status</template_field>
								    </strong>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                    <!-- /three column grid -->

                </div>
                <!--  / contenuto -->

                <div id="push"></div>
            </div>
        </div>
    </div>
    <!-- / contenitore -->

    <!-- com_tools -->
    <div class="clearfix"></div>
    <div id="com_tools">
        <div id="com_toolscontent">
            <template_field class="microtemplate_field" name="com_tools">com_tools</template_field>
        </div>
    </div>
    <!-- /com_tools -->

    <!-- PIEDE -->
    <div id="footer">
        <template_field class="microtemplate_field" name="footer">footer</template_field>
    </div>
    <!-- / piede -->
    <div class="ui modal" id="badgesModal">
        <div class="header">
            <i class="certificate icon"></i>
            <span style="text-transform:capitalize;"><i18n>Badges</i18n></span>
        </div>
        <div class="content"></div>
        <div class="actions">
            <div class="ui button">
                <i18n>OK</i18n>
            </div>
        </div>
    </div>
    <span id="rewardedMSG" style="display:none;"><i18n>Vinto il</i18n> </span>
    <span id="unrewardedMSG" style="display:none;"><i18n>Non vinto</i18n></span>
    <span id="badgesErrorMSG" style="display:none;"><i18n>Errore lettura dati dei badges</i18n></span>
    <span id="noBadgesMSG" style="display:none;"><i18n>Nessun badge per questo corso</i18n></span>
    <span id="rewardsCountMSG" style="display:none;">(<i18n>vinti {rewards} su {countBadges}</i18n>)</span>
</body>

</html>
